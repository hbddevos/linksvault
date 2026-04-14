<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class YouTubeTranscriptService
{
    protected string $baseUrl = 'https://youtubetranscribes.com/api/v2';

    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('YOUTUBE_TRANSCRIBES_API');
    }

    /**
     * Récupère la transcription complète d'une vidéo YouTube sous forme de texte brut
     * (idéal pour générer un résumé ensuite).
     *
     * @param  string  $videoUrl  L'URL de la vidéo YouTube (ex: https://www.youtube.com/watch?v=...)
     * @return string Le texte complet de la transcription
     *
     * @throws Exception
     */
    public function fetchTranscriptText(string $videoUrl): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->post("{$this->baseUrl}/transcripts/", [
                'url' => $videoUrl,
                'language' => 'auto', // Tente de détecter automatiquement la langue
                'caption_type' => 'auto',
                'download_format' => 'json',
            ]);

            // Si le code HTTP n'est pas 200, on délègue à notre gestionnaire d'erreurs
            if ($response->failed()) {
                $this->handleError($response);
            }

            $data = $response->json();

            // Gestion du statut du job d'après la documentation OpenAPI
            if ($data['status'] === 'completed' && ! empty($data['transcripts'])) {
                // Le format 'json' renvoie un tableau d'objets (start, end, text).
                // On extrait uniquement le texte de chaque segment et on les assemble.
                $scripts = collect($data['transcripts'])
                    ->pluck('text')
                    ->implode(' ');

                return [
                    'title' => $data['title'],
                    'scripts' => $scripts,
                ];

            } elseif ($data['status'] === 'processing') {
                throw new Exception('La transcription est en cours de traitement. Veuillez réessayer dans quelques instants.');
            } elseif ($data['status'] === 'failed') {
                throw new Exception("L'extraction de la transcription a échoué. Assurez-vous que la vidéo possède bien des sous-titres.");
            }

            throw new Exception('Statut de réponse inattendu ou aucune transcription trouvée.');
        } catch (RequestException $e) {
            throw new Exception("Erreur de communication avec l'API YouTube Transcribes : ".$e->getMessage());
        }
    }

    /**
     * Traite les différents codes de retour d'erreur HTTP de l'API.
     */
    protected function handleError($response): void
    {
        $status = $response->status();

        // L'API renvoie les erreurs sous forme d'objet { "error": { "code": "...", "message": "..." } }
        $errorData = $response->json('error');
        $errorMessage = $errorData['message'] ?? 'Erreur inconnue de la part de l\'API.';

        $message = match ($status) {
            400 => 'Requête invalide : '.$errorMessage,
            401 => "Erreur d'authentification : Votre clé API YouTube Transcribes est manquante ou invalide.",
            402 => "Crédits insuffisants : Vous n'avez pas assez de crédits sur votre compte YouTube Transcribes.",
            429 => 'Limite de requêtes atteinte : Vous faites trop de requêtes simultanées. Veuillez patienter.',
            default => "Erreur serveur inattendue ($status) : ".$errorMessage,
        };

        throw new Exception($message);
    }
}
