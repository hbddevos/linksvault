<?php

namespace App\Services\GoogleClient\YouTube;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class YouTubeTranscriptCliService
{
    /**
     * Récupérer la transcription avec fallback multi-langues
     * 
     * Cette version utilise l'approche CLI qui accepte plusieurs langues
     * et laisse l'API gérer le fallback automatiquement.
     * 
     * @param string $videoId ID de la vidéo YouTube
     * @param array|string $languages Langue(s) à essayer (peut être une chaîne ou un tableau)
     * @return array|null Transcription ou null en cas d'échec
     */
    public function getTranscript(string $videoId, array|string $languages = ['fr', 'en']): ?array
    {
        // Normaliser les langues en tableau
        if (is_string($languages)) {
            $languages = [$languages];
        }
        
        // Normaliser chaque code de langue
        $normalizedLanguages = array_map(
            fn($lang) => $this->normalizeLanguageCode($lang),
            $languages
        );
        
        $cacheKey = "transcript_cli_" . md5($videoId . '_' . implode('_', $normalizedLanguages));
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($videoId, $normalizedLanguages) {
            return $this->executePythonScript($videoId, $normalizedLanguages);
        });
    }

    /**
     * Exécuter le script Python CLI et retourner le résultat
     */
    protected function executePythonScript(string $videoId, array $languages): ?array
    {
        // Construire la commande avec toutes les langues
        $languagesStr = implode(' ', $languages);
        $command = "python " . storage_path('app/scripts/get_transcript_cli.py') . " {$videoId} {$languagesStr}";
        
        Log::info("Exécution commande transcription CLI", [
            'command' => $command,
            'video_id' => $videoId,
            'languages' => $languages
        ]);
        
        $process = Process::run($command);
        $output = $process->output();
        $errorOutput = $process->errorOutput();
        
        if ($errorOutput) {
            Log::error("Erreur dans le script Python CLI", [
                'error' => $errorOutput,
                'video_id' => $videoId,
                'languages' => $languages
            ]);
        }
        
        if ($output) {
            $result = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Erreur de décodage JSON", [
                    'error' => json_last_error_msg(),
                    'output' => $output,
                    'video_id' => $videoId
                ]);
                return null;
            }
            
            // Vérifier les erreurs retournées par le script
            if (isset($result['error'])) {
                Log::warning("Erreur API transcription CLI", [
                    'error' => $result['error'],
                    'message' => $result['message'] ?? '',
                    'requested_languages' => $result['requested_languages'] ?? $languages,
                    'video_id' => $videoId
                ]);
                return null;
            }
            
            if (isset($result['segments'])) {
                Log::info("Transcription CLI réussie - Stratégie: {$result['strategy_used']}", [
                    'video_id' => $videoId,
                    'requested_languages' => $languages,
                    'actual_language' => $result['language_code'] ?? null,
                    'is_generated' => $result['is_generated'] ?? null,
                    'segments_count' => count($result['segments'])
                ]);
                
                return $result;
            }
        }
        
        return null;
    }

    /**
     * Normaliser le code de langue (extraire ISO 639-1 à 2 lettres)
     * Ex: 'fr-FR' -> 'fr', 'en-US' -> 'en', 'pt-BR' -> 'pt'
     */
    protected function normalizeLanguageCode(string $language): string
    {
        if (str_contains($language, '-')) {
            $normalized = explode('-', $language)[0];
            return strtolower($normalized);
        }
        
        return strtolower($language);
    }

    /**
     * Récupérer en texte brut uniquement
     */
    public function getPlainText(string $videoId, array|string $languages = ['fr', 'en']): ?string
    {
        $transcript = $this->getTranscript($videoId, $languages);
        
        return $transcript['full_text'] ?? null;
    }

    /**
     * Récupérer les sous-titres disponibles
     */
    public function getAvailableLanguages(string $videoId): array
    {
        $command = "python " . storage_path('app/scripts/get_transcript_cli.py') . " {$videoId} list 2>&1";
        
        $output = shell_exec($command);
        
        if ($output) {
            $result = json_decode($output, true);
            if (is_array($result) && !isset($result['error'])) {
                return $result;
            }
        }
        
        return [];
    }

    /**
     * Exporter en format SRT
     */
    public function exportToSRT(string $videoId, array|string $languages = ['fr', 'en']): ?string
    {
        $transcript = $this->getTranscript($videoId, $languages);
        
        if (!$transcript || !isset($transcript['segments'])) {
            return null;
        }

        $srt = '';
        $index = 1;

        foreach ($transcript['segments'] as $segment) {
            $start = $this->formatSRTTime($segment['start']);
            $end = $this->formatSRTTime($segment['start'] + $segment['duration']);
            $text = trim($segment['text']);

            // Nettoyer le texte
            $text = str_replace(['&amp;', '&lt;', '&gt;', '&quot;'], ['&', '<', '>', '"'], $text);
            $text = str_replace(['\n', '\r'], ' ', $text);

            $srt .= "{$index}\n";
            $srt .= "{$start} --> {$end}\n";
            $srt .= "{$text}\n\n";

            $index++;
        }

        return $srt;
    }

    /**
     * Formater le temps pour SRT (HH:MM:SS,mmm)
     */
    protected function formatSRTTime(float $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = floor($seconds % 60);
        $millis = floor(($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $secs, $millis);
    }
}
