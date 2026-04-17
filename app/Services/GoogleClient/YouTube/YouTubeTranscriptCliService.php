<?php

namespace App\Services\GoogleClient\YouTube;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class YouTubeTranscriptCliService
{

    public static function python(){
        if(App::isLocal()){
            return "python";
        }

        return "/home/htesbanzny/miniconda3/bin/python";
    }

    /**
     * Récupérer la transcription en utilisant la commande CLI native
     * 
     * Utilise directement: youtube_transcript_api VIDEO_ID --languages fr en
     * 
     * @param string $videoId ID de la vidéo YouTube
     * @param array $languages Liste des langues prioritaires (défaut: ['fr', 'en'])
     * @return array|null Transcription structurée ou null en cas d'échec
     */
    public function getTranscript(string $videoId, array $languages = ['fr', 'en']): ?array
    {
        // Normaliser les codes de langue
        $normalizedLanguages = array_map(
            fn($lang) => $this->normalizeLanguageCode($lang),
            $languages
        );
        
        $cacheKey = "transcript_cli_" . md5($videoId . '_' . implode('_', $normalizedLanguages));
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($videoId, $normalizedLanguages) {
            return $this->executeCliCommand($videoId, $normalizedLanguages);
        });
    }

    /**
     * Exécuter la commande CLI et parser la sortie
     */
    protected function executeCliCommand(string $videoId, array $languages): ?array
    {
        // Étape 1: Essayer d'abord avec les langues demandées via CLI
        $result = $this->tryFetchWithLanguages($videoId, $languages);
        
        if ($result) {
            return $result;
        }
        
        // Étape 2: Si échec, lister les langues disponibles et choisir intelligemment
        Log::info("Échec avec langues demandées, tentative de détection automatique", [
            'video_id' => $videoId
        ]);
        
        $availableLangs = $this->listAvailableLanguages($videoId);
        
        if (!$availableLangs) {
            Log::error("Impossible de lister les langues disponibles", [
                'video_id' => $videoId
            ]);
            return null;
        }
        
        // Choisir la meilleure langue (fr > en > première disponible)
        $selectedLang = $this->selectBestLanguage($availableLangs);
        
        if (!$selectedLang) {
            Log::error("Aucune langue sélectionnable", [
                'video_id' => $videoId,
                'available_languages' => $availableLangs
            ]);
            return null;
        }
        
        // Réessayer avec la langue choisie
        $result = $this->tryFetchWithLanguages($videoId, [$selectedLang['code']]);
        
        if ($result) {
            $result['strategy_used'] = $selectedLang['strategy'];
            $result['available_languages'] = $availableLangs;
            return $result;
        }
        
        return null;
    }

    /**
     * Essayer de récupérer la transcription avec des langues spécifiques
     */
    protected function tryFetchWithLanguages(string $videoId, array $languages): ?array
    {
        $languagesStr = implode(' ', $languages);
        
        $wrapperScript = storage_path('app/scripts/_cli_wrapper.py');
        $wrapperCode = <<<'PYTHON'
import subprocess
import ast
import json
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

video_id = sys.argv[1]
languages = sys.argv[2:]

try:
    cmd = ['youtube_transcript_api', video_id, '--languages'] + languages
    result = subprocess.run(cmd, capture_output=True, text=True, encoding='utf-8', errors='replace')
    
    if not result.stdout or result.returncode != 0:
        print(json.dumps({"error": "command_failed", "stderr": result.stderr}))
        sys.exit(0)
    
    output = result.stdout.strip()
    data = ast.literal_eval(output)
    
    if isinstance(data, list) and len(data) > 0:
        segments = data[0] if isinstance(data[0], list) else data
    else:
        segments = data
    
    formatted_segments = []
    for seg in segments:
        if isinstance(seg, dict) and 'text' in seg and seg['text'].strip():
            formatted_segments.append({
                'text': seg['text'].strip(),
                'start': seg.get('start', 0),
                'duration': seg.get('duration', 0)
            })
    
    print(json.dumps(formatted_segments, ensure_ascii=False))
    
except Exception as e:
    print(json.dumps({"error": str(e)}))
PYTHON;
        
        file_put_contents($wrapperScript, $wrapperCode);
        
        $process = Process::run(self::python() . " {$wrapperScript} {$videoId} {$languagesStr}");
        $jsonOutput = $process->output();
        
        @unlink($wrapperScript);
        
        if (!$jsonOutput) {
            return null;
        }
        
        $segments = json_decode($jsonOutput, true);
        
        if (isset($segments['error']) || !is_array($segments) || empty($segments)) {
            return null;
        }
        
        $fullText = implode(' ', array_column($segments, 'text'));
        
        return [
            'video_id' => $videoId,
            'language_code' => $languages[0] ?? 'unknown',
            'language' => $this->getLanguageName($languages[0] ?? 'unknown'),
            'is_generated' => true,
            'segments' => $segments,
            'full_text' => $fullText,
            'strategy_used' => "CLI direct avec langues: " . implode(', ', $languages)
        ];
    }

    /**
     * Lister les langues disponibles via la CLI --list
     */
    protected function listAvailableLanguages(string $videoId): ?array
    {
        $process = Process::run("youtube_transcript_api {$videoId} --list");
        $output = $process->output();
        
        if (!$output) {
            return null;
        }
        
        // Parser la sortie texte de --list
        return $this->parseListOutput($output);
    }

    /**
     * Parser la sortie texte de --list
     */
    protected function parseListOutput(string $output): array
    {
        $languages = [];
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Chercher les lignes avec format: - fr ("French (auto-generated)")
            if (preg_match('/^\s*-\s+(\w+)\s+\("([^"]+)"\)/', $line, $matches)) {
                $code = $matches[1];
                $name = $matches[2];
                $isGenerated = stripos($name, 'auto-generated') !== false || stripos($name, 'generated') !== false;
                
                $languages[] = [
                    'code' => $code,
                    'name' => $name,
                    'is_generated' => $isGenerated
                ];
            }
        }
        
        return $languages;
    }

    /**
     * Sélectionner la meilleure langue selon la priorité
     */
    protected function selectBestLanguage(array $availableLangs): ?array
    {
        if (empty($availableLangs)) {
            return null;
        }
        
        // Priorité 1: Français
        foreach ($availableLangs as $lang) {
            if (stripos($lang['code'], 'fr') === 0) {
                $lang['strategy'] = "Langue sélectionnée: {$lang['name']} (priorité FR)";
                return $lang;
            }
        }
        
        // Priorité 2: Anglais
        foreach ($availableLangs as $lang) {
            if (stripos($lang['code'], 'en') === 0) {
                $lang['strategy'] = "Langue sélectionnée: {$lang['name']} (fallback EN)";
                return $lang;
            }
        }
        
        // Priorité 3: Première disponible
        $first = $availableLangs[0];
        $first['strategy'] = "Langue sélectionnée: {$first['name']} (première disponible)";
        return $first;
    }

    /**
     * Parser la sortie brute Python (format: [[{...}]])
     */
    protected function parsePythonOutput(string $output): ?array
    {
        try {
            // Nettoyer la sortie
            $cleaned = trim($output);
            
            // Écrire dans un fichier temporaire pour éviter les limites de ligne de commande
            $tempInputFile = storage_path('app/scripts/_transcript_input.txt');
            file_put_contents($tempInputFile, $cleaned);
            
            // Script Python pour parser et convertir en JSON
            $parserScript = storage_path('app/scripts/_parse_transcript_output.py');
            $parserCode = <<<'PYTHON'
import ast
import sys
import json

try:
    with open(sys.argv[1], 'r', encoding='utf-8', errors='replace') as f:
        content = f.read()
    
    # Remplacer les caractères problématiques avant parsing
    data = ast.literal_eval(content)
    
    # La structure est [[{...}]] - prendre le premier élément
    if isinstance(data, list) and len(data) > 0:
        if isinstance(data[0], list):
            result = data[0]
        else:
            result = data
    else:
        result = data
    
    print(json.dumps(result, ensure_ascii=False))
except Exception as e:
    print(json.dumps({"error": str(e)}))
PYTHON;
            
            file_put_contents($parserScript, $parserCode);
            
            // Exécuter le parser
            $process = Process::run(self::python() . " {$parserScript} {$tempInputFile}");
            $jsonOutput = $process->output();
            
            // Nettoyer les fichiers temporaires
            @unlink($tempInputFile);
            @unlink($parserScript);
            
            if (!$jsonOutput) {
                return null;
            }
            
            $parsed = json_decode($jsonOutput, true);
            
            // Vérifier s'il y a eu une erreur
            if (isset($parsed['error'])) {
                Log::error("Erreur parsing Python", [
                    'error' => $parsed['error']
                ]);
                return null;
            }
            
            return $parsed;
            
        } catch (\Exception $e) {
            Log::error("Exception lors du parsing", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extraire les segments de la structure parsée
     */
    protected function extractSegments(?array $parsed): array
    {
        // Plus utilisé - la logique est maintenant dans le wrapper Python
        return [];
    }

    /**
     * Tronquer le texte s'il est trop long
     */
    public function truncateText(string $text, int $maxWords = 1000): string
    {
        $words = str_word_count($text);
        
        if ($words <= $maxWords) {
            return $text;
        }
        
        // Tronquer au nombre de mots maximum
        $truncated = implode(' ', array_slice(explode(' ', $text), 0, $maxWords));
        
        return $truncated . '...';
    }

    /**
     * Récupérer en texte brut uniquement (tronqué si nécessaire)
     */
    public function getPlainText(string $videoId, array $languages = ['fr', 'en'], int $maxWords = 1000): ?string
    {
        $transcript = $this->getTranscript($videoId, $languages);
        
        if (!$transcript || !isset($transcript['full_text'])) {
            return null;
        }
        
        return $this->truncateText($transcript['full_text'], $maxWords);
    }

    /**
     * Normaliser le code de langue
     */
    protected function normalizeLanguageCode(string $language): string
    {
        if (str_contains($language, '-')) {
            return strtolower(explode('-', $language)[0]);
        }
        
        return strtolower($language);
    }

    /**
     * Obtenir le nom complet de la langue
     */
    protected function getLanguageName(string $code): string
    {
        $languages = [
            'fr' => 'French',
            'en' => 'English',
            'es' => 'Spanish',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'ru' => 'Russian',
        ];
        
        return $languages[$code] ?? ucfirst($code);
    }

    /**
     * Récupérer les langues disponibles (nécessite un appel séparé)
     */
    public function getAvailableLanguages(string $videoId): array
    {
        // Pour lister les langues, on peut essayer d'appeler sans --languages
        // ou utiliser l'ancien script Python
        $command = self::python() . " " . storage_path('app/scripts/get_transcript.py') . " {$videoId} list 2>&1";
        
        $output = Process::run($command)->output();
        
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
    public function exportToSRT(string $videoId, array $languages = ['fr', 'en']): ?string
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
