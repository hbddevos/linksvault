<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * YouTube Data API v3 base URL.
     */
    protected const API_BASE = 'https://www.googleapis.com/youtube/v3';

    /**
     * YouTube oEmbed endpoint.
     */
    protected const OEMBED_URL = 'https://www.youtube.com/oembed';

    public function __construct(
        protected ContentDetectionService $contentDetection,
    ) {}

    /**
     * Get video metadata from YouTube API with caching.
     *
     * @return array{video_id: string|null, title: string|null, description: string|null, channel: string|null, published_at: string|null, duration: string|null, views: int|null, thumbnail: string|null, embed_html: string|null}|null
     */
    public function getVideoMetadata(string $url): ?array
    {
        $videoId = $this->contentDetection->extractYouTubeVideoId($url);

        if (! $videoId) {
            return null;
        }

        $cacheKey = "youtube.video.{$videoId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($videoId, $url) {
            return $this->fetchVideoData($videoId, $url);
        });
    }

    /**
     * Fetch video data from YouTube oEmbed API (no API key required).
     *
     * @return array{video_id: string|null, title: string|null, description: string|null, channel: string|null, published_at: string|null, duration: string|null, views: int|null, thumbnail: string|null, embed_html: string|null}
     */
    protected function fetchVideoData(string $videoId, string $url): array
    {
        $data = [
            'video_id' => $videoId,
            'title' => null,
            'description' => null,
            'channel' => null,
            'published_at' => null,
            'duration' => null,
            'views' => null,
            'thumbnail' => null,
            'embed_html' => null,
        ];

        try {
            $response = Http::timeout(10)->get(self::OEMBED_URL, [
                'url' => $url,
                'format' => 'json',
            ]);

            if ($response->successful()) {
                $oembed = $response->json();

                $data['title'] = $oembed['title'] ?? null;
                $data['channel'] = $oembed['author_name'] ?? null;
                $data['thumbnail'] = $oembed['thumbnail_url'] ?? null;
                $data['embed_html'] = $oembed['html'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch YouTube oEmbed data', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to thumbnail from known YouTube URL pattern
        if (! $data['thumbnail']) {
            $data['thumbnail'] = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
        }

        return $data;
    }

    /**
     * Get video metadata using YouTube Data API v3 (requires API key).
     * This provides richer data including duration, view count, etc.
     *
     * @return array<string, mixed>|null
     */
    public function getVideoMetadataWithApiKey(string $url, ?string $apiKey = null): ?array
    {
        $videoId = $this->contentDetection->extractYouTubeVideoId($url);

        if (! $videoId) {
            return null;
        }

        $cacheKey = "youtube.video_api.{$videoId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($videoId, $apiKey) {
            $apiKey = $apiKey ?? config('services.youtube.api_key');

            if (! $apiKey) {
                return null;
            }

            try {
                $response = Http::timeout(10)->get(self::API_BASE.'/videos', [
                    'part' => 'snippet,contentDetails,statistics',
                    'id' => $videoId,
                    'key' => $apiKey,
                ]);

                if ($response->successful()) {
                    $body = $response->json();
                    $items = $body['items'] ?? [];

                    if (! empty($items)) {
                        $item = $items[0];
                        $snippet = $item['snippet'] ?? [];
                        $contentDetails = $item['contentDetails'] ?? [];
                        $statistics = $item['statistics'] ?? [];

                        return [
                            'video_id' => $videoId,
                            'title' => $snippet['title'] ?? null,
                            'description' => $snippet['description'] ?? null,
                            'channel' => $snippet['channelTitle'] ?? null,
                            'published_at' => $snippet['publishedAt'] ?? null,
                            'duration' => $contentDetails['duration'] ?? null,
                            'views' => isset($statistics['viewCount']) ? (int) $statistics['viewCount'] : null,
                            'thumbnail' => $snippet['thumbnails']['high']['url'] ?? null,
                            'embed_html' => null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch YouTube API data', [
                    'video_id' => $videoId,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }

    /**
     * Get embed HTML for a YouTube video.
     */
    public function getEmbedHtml(string $videoId, int $width = 560, int $height = 315): string
    {
        return sprintf(
            '<iframe width="%d" height="%d" src="https://www.youtube.com/embed/%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
            $width,
            $height,
            e($videoId)
        );
    }

    /**
     * Check if a URL is a valid YouTube video.
     */
    public function isValidYouTubeUrl(string $url): bool
    {
        return $this->contentDetection->extractYouTubeVideoId($url) !== null;
    }

    /**
     * Flush cache for a specific video.
     */
    public function flushCache(string $videoId): void
    {
        Cache::forget("youtube.video.{$videoId}");
        Cache::forget("youtube.video_api.{$videoId}");
    }

    /**
     * Flush all YouTube cache entries.
     */
    public function flushAllCache(): void
    {
        Cache::tags(['youtube'])->flush();
    }
}
