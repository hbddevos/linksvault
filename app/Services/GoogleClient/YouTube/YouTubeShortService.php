<?php

namespace App\Services\GoogleClient\YouTube;

use Google_Service_YouTube;
use Google_Client;

class YouTubeShortService
{
    protected Google_Service_YouTube $youtube;
    protected Google_Client $client;
    protected YouTubeVideoService $videoService;

    public const SHORT_MAX_DURATION = 60; // 60 secondes max pour un Short

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setDeveloperKey(config('google.api_key'));
        $this->youtube = new Google_Service_YouTube($this->client);
        $this->videoService = new YouTubeVideoService();
    }

    /**
     * Récupérer les informations d'un Short
     */
    public function getShortInfo(string $shortId): ?array
    {
        $video = $this->videoService->getVideoInfo($shortId);

        if (!$video) {
            return null;
        }

        // Vérifier si c'est vraiment un Short
        if (!$this->isShortVideo($video)) {
            return null;
        }

        return array_merge($video, [
            'is_short' => true,
            'type' => 'short',
            'url' => "https://youtube.com/shorts/{$shortId}",
            'short_url' => "https://youtu.be/{$shortId}",
        ]);
    }

    /**
     * Vérifier si une vidéo est un Short
     */
    public function isShortVideo(array $video): bool
    {
        if (!isset($video['duration'])) {
            return false;
        }

        $durationSeconds = $this->videoService->parseDuration($video['duration']);
        
        // Les Shorts YouTube ont une durée <= 60 secondes
        return $durationSeconds <= self::SHORT_MAX_DURATION;
    }

    /**
     * Récupérer les Shorts d'une chaîne
     */
    public function getChannelShorts(string $channelId, int $maxResults = 20): array
    {
        try {
            // Recherche de Shorts sur la chaîne
            $searchResponse = $this->youtube->search->listSearch(
                'snippet,id',
                [
                    'channelId' => $channelId,
                    'maxResults' => $maxResults,
                    'type' => 'video',
                    'videoDuration' => 'short', // Filtre automatique pour Shorts
                ]
            );

            $shorts = [];
            foreach ($searchResponse->items as $item) {
                $videoId = $item['id']['videoId'] ?? null;
                
                if ($videoId) {
                    // Récupérer les détails complets
                    $videoDetails = $this->videoService->getVideoInfo($videoId);
                    
                    if ($videoDetails) {
                        $shorts[] = array_merge($videoDetails, [
                            'is_short' => true,
                            'type' => 'short',
                            'url' => "https://youtube.com/shorts/{$videoId}",
                        ]);
                    }
                }
            }

            return $shorts;

        } catch (\Exception $e) {
            \Log::error('YouTube Shorts Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Rechercher des Shorts par terme
     */
    public function searchShorts(string $query, int $maxResults = 20): array
    {
        try {
            $searchResponse = $this->youtube->search->listSearch(
                'snippet,id',
                [
                    'q' => $query,
                    'maxResults' => $maxResults,
                    'type' => 'video',
                    'videoDuration' => 'short',
                ]
            );

            $shorts = [];
            foreach ($searchResponse->items as $item) {
                $videoId = $item['id']['videoId'] ?? null;
                
                if ($videoId) {
                    $shorts[] = [
                        'video_id' => $videoId,
                        'title' => $item['snippet']['title'],
                        'description' => $item['snippet']['description'],
                        'published_at' => $item['snippet']['publishedAt'],
                        'channel_id' => $item['snippet']['channelId'],
                        'channel_title' => $item['snippet']['channelTitle'],
                        'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? '',
                        'is_short' => true,
                        'type' => 'short',
                        'url' => "https://youtube.com/shorts/{$videoId}",
                    ];
                }
            }

            return $shorts;

        } catch (\Exception $e) {
            \Log::error('YouTube Shorts Search Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les Shorts d'une playlist
     */
    public function getPlaylistShorts(string $playlistId): array
    {
        $playlistService = new YouTubePlaylistService();
        $allVideos = $playlistService->getPlaylistItems($playlistId);

        $shorts = array_filter($allVideos, function ($video) {
            $videoDetails = $this->videoService->getVideoInfo($video['video_id']);
            return $videoDetails && $this->isShortVideo($videoDetails);
        });

        return array_map(function ($video) {
            return array_merge($video, [
                'is_short' => true,
                'type' => 'short',
                'url' => "https://youtube.com/shorts/{$video['video_id']}",
            ]);
        }, array_values($shorts));
    }

    /**
     * Analyser une URL et extraire l'ID du Short
     */
    public function extractShortId(string $url): ?string
    {
        // Formats supportés:
        // https://youtube.com/shorts/VIDEO_ID
        // https://youtu.be/VIDEO_ID (si <= 60s)
        // https://www.youtube.com/shorts/VIDEO_ID

        // Format: /shorts/VIDEO_ID
        if (preg_match('/\/shorts\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        // Format: youtu.be/VIDEO_ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        // Format: v=VIDEO_ID
        if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Valider et récupérer les infos d'un Short depuis une URL
     */
    public function getShortFromUrl(string $url): ?array
    {
        $shortId = $this->extractShortId($url);

        if (!$shortId) {
            return null;
        }

        return $this->getShortInfo($shortId);
    }
}