<?php

namespace App\Services\GoogleClient\YouTube;

use Google_Service_YouTube;
use Google_Client;

class YouTubeVideoService
{
    protected Google_Service_YouTube $youtube;
    protected Google_Client $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setDeveloperKey(config('google.api_key'));
        $this->youtube = new Google_Service_YouTube($this->client);
    }

    /**
     * Récupérer les informations d'une vidéo par son ID
     */
    public function getVideoInfo(string $videoId): ?array
    {
        try {
            $response = $this->youtube->videos->listVideos(
                'snippet,contentDetails,statistics,recordingDetails,topicDetails,status,player',
                [
                    'id' => $videoId,
                    'maxResults' => 1
                ]
            );

            if (empty($response->items)) {
                return null;
            }

            return $this->formatVideoData($response->items[0]);
            
        } catch (\Exception $e) {
            \Log::error('YouTube API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer plusieurs vidéos par leurs IDs
     */
    public function getMultipleVideos(array $videoIds): array
    {
        try {
            $response = $this->youtube->videos->listVideos(
                'snippet,contentDetails,statistics,status',
                [
                    'id' => implode(',', $videoIds),
                    'maxResults' => 50
                ]
            );

            return array_map(function ($item) {
                return $this->formatVideoData($item);
            }, $response->items);

        } catch (\Exception $e) {
            \Log::error('YouTube API Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Rechercher des vidéos par mots-clés
     */
    public function searchVideos(string $query, int $maxResults = 10): array
    {
        try {
            $searchResponse = $this->youtube->search->listSearch(
                'snippet,id',
                [
                    'q' => $query,
                    'maxResults' => $maxResults,
                    'type' => 'video',
                    'order' => 'relevance'
                ]
            );

            return array_map(function ($item) {
                return [
                    'video_id' => $item['id']['videoId'] ?? null,
                    'title' => $item['snippet']['title'] ?? '',
                    'description' => $item['snippet']['description'] ?? '',
                    'published_at' => $item['snippet']['publishedAt'] ?? '',
                    'channel_id' => $item['snippet']['channelId'] ?? '',
                    'channel_title' => $item['snippet']['channelTitle'] ?? '',
                    'thumbnail_default' => $item['snippet']['thumbnails']['default']['url'] ?? '',
                    'thumbnail_medium' => $item['snippet']['thumbnails']['medium']['url'] ?? '',
                    'thumbnail_high' => $item['snippet']['thumbnails']['high']['url'] ?? '',
                ];
            }, $searchResponse->items);

        } catch (\Exception $e) {
            \Log::error('YouTube Search Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Formater les données d'une vidéo
     */
    protected function formatVideoData($video): array
    {
        $snippet = $video['snippet'] ?? [];
        $contentDetails = $video['contentDetails'] ?? [];
        $statistics = $video['statistics'] ?? [];
        $status = $video['status'] ?? [];
        $player = $video['player'] ?? [];

        return [
            'id' => $video['id'] ?? '',
            'title' => $snippet['title'] ?? '',
            'description' => $snippet['description'] ?? '',
            'published_at' => $snippet['publishedAt'] ?? '',
            'channel_id' => $snippet['channelId'] ?? '',
            'channel_title' => $snippet['channelTitle'] ?? '',
            'category_id' => $snippet['categoryId'] ?? '',
            'tags' => $snippet['tags'] ?? [],
            'player' => $player['embedHtml'] ?? [],
            
            // Duration ISO 8601 (ex: PT1H2M3S)
            'duration' => $contentDetails['duration'] ?? '',
            'dimension' => $contentDetails['dimension'] ?? '',
            'definition' => $contentDetails['definition'] ?? '',
            'caption' => $contentDetails['caption'] ?? false,
            'licensed_content' => $contentDetails['licensedContent'] ?? false,
            
            // Statistiques
            'view_count' => $statistics['viewCount'] ?? 0,
            'like_count' => $statistics['likeCount'] ?? 0,
            'dislike_count' => $statistics['dislikeCount'] ?? 0,
            'favorite_count' => $statistics['favoriteCount'] ?? 0,
            'comment_count' => $statistics['commentCount'] ?? 0,
            
            // Status
            'privacy_status' => $status['privacyStatus'] ?? '',
            'upload_status' => $status['uploadStatus'] ?? '',
            'license' => $status['license'] ?? '',
            'embeddable' => $status['embeddable'] ?? true,
            
            // Thumbnails
            'thumbnail_default' => $snippet['thumbnails']['default']['url'] ?? '',
            'thumbnail_medium' => $snippet['thumbnails']['medium']['url'] ?? '',
            'thumbnail_high' => $snippet['thumbnails']['high']['url'] ?? '',
            'thumbnail_maxres' => $snippet['thumbnails']['maxres']['url'] ?? '',
        ];
    }

    /**
     * Vérifier si une vidéo est un Short (duration <= 60s)
     */
    public function isShort(string $videoId): bool
    {
        $video = $this->getVideoInfo($videoId);
        
        if (!$video || !isset($video['duration'])) {
            return false;
        }

        $duration = $this->parseDuration($video['duration']);
        return $duration <= 60;
    }

    /**
     * Parser une durée ISO 8601 en secondes
     */
    public function parseDuration(string $isoDuration): int
    {
        preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $isoDuration, $matches);
        
        $hours = intval($matches[1] ?? 0);
        $minutes = intval($matches[2] ?? 0);
        $seconds = intval($matches[3] ?? 0);
        
        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
}