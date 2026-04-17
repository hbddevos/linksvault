<?php

namespace App\Services\GoogleClient\YouTube;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class YouTubeTranscriptService
{
    /**
     * Récupérer la transcription (version simplifiée)
     *
     * Le script Python gère maintenant intelligemment le fallback multi-langues
     */
    public function getTranscript(string $videoId, string $language = 'fr'): ?array
    {
        // Normaliser le code de langue (extraire uniquement les 2 premières lettres)
        $normalizedLang = $this->normalizeLanguageCode($language);

        $cacheKey = "transcript_{$videoId}_{$normalizedLang}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($videoId, $normalizedLang) {
            // Le script Python gère automatiquement le fallback intelligent
            // (langue préférée → variantes → anglais → première disponible)
            return $this->executePythonScript($videoId, $normalizedLang);
        });
    }

    /**
     * Exécuter le script Python et retourner le résultat
     */
    protected function executePythonScript(string $videoId, string $language): ?array
    {
        $command = 'python '.storage_path('app/scripts/get_transcript.py')." {$videoId} {$language}";

        Log::info('Exécution commande transcription', [
            'command' => $command,
            'video_id' => $videoId,
            'language' => $language,
        ]);

        $process = Process::run($command);
        $output = $process->output();
        $errorOutput = $process->errorOutput();

        if ($errorOutput) {
            Log::error('Erreur dans le script Python', [
                'error' => $errorOutput,
                'video_id' => $videoId,
                'language' => $language,
            ]);
        }

        if ($output) {
            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Erreur de décodage JSON', [
                    'error' => json_last_error_msg(),
                    'output' => $output,
                    'video_id' => $videoId,
                ]);

                return null;
            }

            // Le script retourne maintenant une erreur structurée avec détails
            if (isset($result['error'])) {
                Log::warning('Erreur API transcription', [
                    'error' => $result['error'],
                    'message' => $result['message'] ?? '',
                    'available_languages' => $result['available_languages'] ?? [],
                    'video_id' => $videoId,
                    'language' => $language,
                ]);

                return null;
            }

            if (isset($result['segments'])) {
                // Logger la stratégie utilisée (nouveau champ du script Python)
                if (isset($result['strategy_used'])) {
                    Log::info("Transcription réussie - Stratégie: {$result['strategy_used']}", [
                        'video_id' => $videoId,
                        'language' => $language,
                        'actual_language' => $result['language_code'] ?? null,
                        'is_generated' => $result['is_generated'] ?? null,
                        'segments_count' => count($result['segments']),
                    ]);
                } else {
                    Log::info('Transcription réussie', [
                        'video_id' => $videoId,
                        'language' => $language,
                        'segments_count' => count($result['segments']),
                    ]);
                }

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
        // Si le code contient un tiret, prendre uniquement la partie avant
        if (str_contains($language, '-')) {
            $normalized = explode('-', $language)[0];
            Log::debug('Normalisation code langue', [
                'original' => $language,
                'normalized' => $normalized,
            ]);

            return strtolower($normalized);
        }

        return strtolower($language);
    }

    /**
     * Récupérer en texte brut uniquement
     */
    public function getPlainText(string $videoId, string $language = 'fr'): ?string
    {
        $transcript = $this->getTranscript($videoId, $language);

        return $transcript['full_text'] ?? null;
    }

    /**
     * Récupérer les sous-titres disponibles
     */
    public function getAvailableLanguages(string $videoId): array
    {
        $command = 'python '.storage_path('app/scripts/get_transcript.py')." {$videoId} list 2>&1";

        $output = shell_exec($command);

        if ($output) {
            $result = json_decode($output, true);
            if (is_array($result) && ! isset($result['error'])) {
                return $result;
            }
        }

        return [];
    }

    /**
     * Exporter en format SRT
     */
    public function exportToSRT(string $videoId, string $language = 'fr'): ?string
    {
        $transcript = $this->getTranscript($videoId, $language);

        if (! $transcript || ! isset($transcript['segments'])) {
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
