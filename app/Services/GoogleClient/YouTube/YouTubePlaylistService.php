<?php

namespace App\Services\GoogleClient\YouTube;

use Google_Service_YouTube;
use Google_Client;

class YouTubePlaylistService
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
     * Récupérer les informations d'une playlist
     */
    public function getPlaylistInfo(string $playlistId): ?array
    {
        try {
            $response = $this->youtube->playlists->listPlaylists(
                'snippet,status,contentDetails,localization,player',
                [
                    'id' => $playlistId,
                    'maxResults' => 1
                ]
            );

            if (empty($response->items)) {
                return null;
            }

            return $this->formatPlaylistData($response->items[0]);

        } catch (\Exception $e) {
            \Log::error('YouTube Playlist API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer toutes les vidéos d'une playlist (avec pagination)
     */
    public function getPlaylistItems(string $playlistId, int $maxResults = 50): array
    {
        $items = [];
        $nextPageToken = null;

        try {
            do {
                $params = [
                    'playlistId' => $playlistId,
                    'maxResults' => min($maxResults, 50),
                    'part' => 'snippet,contentDetails,status'
                ];

                if ($nextPageToken) {
                    $params['pageToken'] = $nextPageToken;
                }

                $response = $this->youtube->playlistItems->listPlaylistItems(
                    'snippet,contentDetails,status',
                    $params
                );

                foreach ($response->items as $item) {
                    $items[] = [
                        'video_id' => $item['contentDetails']['videoId'] ?? '',
                        'title' => $item['snippet']['title'] ?? '',
                        'description' => $item['snippet']['description'] ?? '',
                        'published_at' => $item['snippet']['publishedAt'] ?? '',
                        'position' => $item['snippet']['position'] ?? 0,
                        'channel_id' => $item['snippet']['channelId'] ?? '',
                        'channel_title' => $item['snippet']['channelTitle'] ?? '',
                        'thumbnail_default' => $item['snippet']['thumbnails']['default']['url'] ?? '',
                        'thumbnail_medium' => $item['snippet']['thumbnails']['medium']['url'] ?? '',
                        'thumbnail_high' => $item['snippet']['thumbnails']['high']['url'] ?? '',
                        'video_exists' => isset($item['status']['privacyStatus']),
                    ];
                }

                $nextPageToken = $response->nextPageToken;
                $maxResults -= 50;

            } while ($nextPageToken && $maxResults > 0);

        } catch (\Exception $e) {
            \Log::error('YouTube Playlist Items Error: ' . $e->getMessage());
        }

        return $items;
    }

    /**
     * Récupérer les playlists d'une chaîne
     */
    public function getChannelPlaylists(string $channelId, int $maxResults = 25): array
    {
        try {
            $response = $this->youtube->playlists->listPlaylists(
                'snippet,contentDetails,status',
                [
                    'channelId' => $channelId,
                    'maxResults' => $maxResults,
                    'type' => 'any' // 'any' ou 'history' pour watch_history
                ]
            );

            return array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'title' => $item['snippet']['title'],
                    'description' => $item['snippet']['description'],
                    'published_at' => $item['snippet']['publishedAt'],
                    'channel_id' => $item['snippet']['channelId'],
                    'channel_title' => $item['snippet']['channelTitle'],
                    'thumbnail_default' => $item['snippet']['thumbnails']['default']['url'] ?? '',
                    'thumbnail_medium' => $item['snippet']['thumbnails']['medium']['url'] ?? '',
                    'thumbnail_high' => $item['snippet']['thumbnails']['high']['url'] ?? '',
                    'item_count' => $item['contentDetails']['itemCount'] ?? 0,
                    'privacy_status' => $item['status']['privacyStatus'],
                ];
            }, $response->items);

        } catch (\Exception $e) {
            \Log::error('YouTube Channel Playlists Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer une playlist complète avec métadonnées
     */
    public function getPlaylistFull(string $playlistId): ?array
    {
        $playlist = $this->getPlaylistInfo($playlistId);

        if (!$playlist) {
            return null;
        }

        $playlist['items'] = $this->getPlaylistItems($playlistId);
        $playlist['videos_count'] = count($playlist['items']);

        return $playlist;
    }

    /**
     * Formater les données d'une playlist
     */
    protected function formatPlaylistData($playlist): array
    {
        $snippet = $playlist['snippet'] ?? [];
        $contentDetails = $playlist['contentDetails'] ?? [];
        $status = $playlist['status'] ?? [];

        return [
            'id' => $playlist['id'],
            'title' => $snippet['title'] ?? '',
            'description' => $snippet['description'] ?? '',
            'published_at' => $snippet['publishedAt'] ?? '',
            'channel_id' => $snippet['channelId'] ?? '',
            'channel_title' => $snippet['channelTitle'] ?? '',
            'tags' => $snippet['tags'] ?? [],
            
            'item_count' => $contentDetails['itemCount'] ?? 0,
            
            'privacy_status' => $status['privacyStatus'] ?? '',
            'kind' => $status['kind'] ?? '',
            
            'thumbnail_default' => $snippet['thumbnails']['default']['url'] ?? '',
            'thumbnail_medium' => $snippet['thumbnails']['medium']['url'] ?? '',
            'thumbnail_high' => $snippet['thumbnails']['high']['url'] ?? '',
        ];
    }
}