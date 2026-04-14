<?php

namespace App\Services;

use Alaouy\Youtube\Facades\Youtube;
use App\Models\Link;

class ContentDetectionService
{
    /**
     * Supported content types in priority order.
     *
     * @var array<int, string>
     */
    protected const CONTENT_TYPES = [
        'youtube',
        'youtube_playlist',
        'google_doc',
        'google_slides',
        'google_sheet',
        'google_form',
        'google_drive',
        'pdf',
        'image',
        'article',
        'other',
    ];

    /**
     * YouTube URL patterns.
     *
     * @var array<int, string>
     */
    protected const YOUTUBE_PATTERNS = [
        'youtube.com/watch',
        'youtu.be/',
        'youtube.com/shorts/',
        'youtube.com/embed/',
        'youtube.com/v/',
    ];

    /**
     * Google Drive URL patterns.
     *
     * @var array<int, string>
     */
    protected const DRIVE_PATTERNS = [
        'drive.google.com',
        'docs.google.com',
        'drive.google.com/drive/folders',
        'drive.google.com/file/d',
        'docs.google.com/spreadsheets/d',
        'docs.google.com/document/d',
        'docs.google.com/presentation/d',
        'docs.google.com/forms/d',
    ];

    /**
     * PDF file extensions in URL.
     *
     * @var array<int, string>
     */
    protected const PDF_EXTENSIONS = ['.pdf'];

    /**
     * Image file extensions in URL.
     *
     * @var array<int, string>
     */
    protected const IMAGE_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp', '.ico'];

    /**
     * Detect content type from a URL.
     * return a type of link, youtube, google_doc etc...
     */
    public function detectType(string $url): string
    {
        $normalized = strtolower(trim($url));
        $parsed = parse_url($normalized);
        $path = $parsed['path'] ?? '';
        $host = $parsed['host'] ?? '';
        $query = $parsed['query'] ?? '';

        // Check YouTube patterns first
        if ($this->isYouTube($host, $path)) {
            return 'youtube';
        }

        // Check Google Drive patterns
        if ($this->isGoogleDrive($host)) {
            if (str_contains($host, 'docs.google.com') || str_contains($path, '/d/')) {
                if (str_contains($path, '/document/')) {
                    return 'google_doc';
                }
                if (str_contains($path, '/spreadsheets/')) {
                    return 'google_sheet';
                }
                if (str_contains($path, '/presentation/')) {
                    return 'google_slides';
                }
                if (str_contains($path, '/forms/')) {
                    return 'google_form';
                }
            }

            return 'google_drive';
        }

        // Check file extension for PDF
        if ($this->hasExtension($path, self::PDF_EXTENSIONS)) {
            return 'pdf';
        }

        // Check file extension for images
        if ($this->hasExtension($path, self::IMAGE_EXTENSIONS)) {
            return 'image';
        }

        // Default to article for regular HTTP URLs
        return 'article';
    }

    /**
     * Extract metadata from a URL based on content type.
     *
     * @return array{video_id?: string, duration?: string|null, channel?: string|null, published_at?: string|null, views?: int|null, mime_type?: string, size?: int|null, permissions?: string|null, shared?: bool, author?: string|null, read_time?: int|null, og_image?: string|null}
     */
    public function extractMetadata(string $url, string $contentType): array
    {
        return match ($contentType) {
            'youtube' => $this->extractYouTubeMetadata($url),
            'google_drive', 'google_doc', 'google_slides', 'google_sheet', 'google_form' => $this->extractDriveMetadata($url),
            'article' => $this->extractArticleMetadata($url),
            'pdf' => $this->extractPdfMetadata($url),
            'image' => $this->extractImageMetadata($url),
            default => [],
        };
    }

    /**
     * Detect content type and extract metadata in one call.
     *
     * @return array{type: string, metadata: array<string, mixed>}
     */
    public function analyze(string $url): array
    {
        $type = $this->detectType($url);
        $metadata = $this->extractMetadata($url, $type);

        return [
            'type' => $type,
            'metadata' => $metadata,
        ];
    }

    /**
     * Extract YouTube video ID from various URL formats.
     */
    public function extractYouTubeVideoId(string $url): ?string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $query = $parsed['query'] ?? '';

        // youtu.be/VIDEO_ID
        if (str_contains($host, 'youtu.be')) {
            return trim($path, '/');
        }

        // youtube.com/watch?v=VIDEO_ID
        if (str_contains($host, 'youtube.com') && str_contains($path, '/watch')) {
            parse_str($query, $params);

            return $params['v'] ?? null;
        }

        // youtube.com/shorts/VIDEO_ID
        if (str_contains($path, '/shorts/')) {
            $parts = explode('/shorts/', $path);

            return rtrim($parts[1] ?? null, '/');
        }

        // youtube.com/embed/VIDEO_ID or youtube.com/v/VIDEO_ID
        if (preg_match('#/(embed|v)/([^/?&]+)#', $path, $matches)) {
            return $matches[2];
        }

        return null;
    }

    /**
     * Update a link with detected content type and metadata.
     */
    public function updateLinkWithTypeAndMetadata(Link $link, ?array $embedMetadata = null): Link
    {
        $analysis = $this->analyze($link->url);

        $update = [
            'content_type' => $analysis['type'],
            'metadata' => array_merge($link->metadata ?? [], $analysis['metadata']),
        ];

        if ($embedMetadata !== null) {
            $update['metadata'] = array_merge($update['metadata'], $embedMetadata);
        }

        // Extract title from URL if not set
        if (empty($link->title)) {
            $update['title'] = $this->generateTitleFromUrl($link->url, $analysis['type']);
        }

        $link->update($update);

        return $link;
    }

    /**
     * Check if URL matches YouTube patterns.
     */
    protected function isYouTube(string $host, string $path): bool
    {
        $fullUrl = $host . $path;

        foreach (self::YOUTUBE_PATTERNS as $pattern) {
            if (str_contains($fullUrl, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if URL matches Google Drive patterns.
     */
    protected function isGoogleDrive(string $host): bool
    {
        foreach (self::DRIVE_PATTERNS as $pattern) {
            if (str_contains($host, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if URL path has any of the given extensions.
     *
     * @param  array<int, string>  $extensions
     */
    protected function hasExtension(string $path, array $extensions): bool
    {
        $pathLower = strtolower($path);

        foreach ($extensions as $ext) {
            if (str_ends_with($pathLower, $ext)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract metadata for YouTube URLs.
     *
     * @return array{video_id?: string}
     */
    protected function extractYouTubeMetadata(string $url): array
    {
        $videoId = $this->extractYouTubeVideoId($url);

        return $videoId ? ['video_id' => $videoId] : [];
    }

    /**
     * Extract metadata for Google Drive URLs.
     *
     * @return array{mime_type?: string, shared?: bool}
     */
    protected function extractDriveMetadata(string $url): array
    {
        $isShared = str_contains($url, '/file/d/') || str_contains($url, 'usp=sharing');

        return ['shared' => $isShared];
    }

    /**
     * Extract metadata for article URLs.
     *
     * @return array<string, mixed>
     */
    protected function extractArticleMetadata(string $url): array
    {
        // Basic metadata - would be enriched by embed/embed package
        return [];
    }

    /**
     * Extract metadata for PDF URLs.
     *
     * @return array{mime_type: string}
     */
    protected function extractPdfMetadata(string $url): array
    {
        return ['mime_type' => 'application/pdf'];
    }

    /**
     * Extract metadata for image URLs.
     *
     * @return array{mime_type?: string}
     */
    protected function extractImageMetadata(string $url): array
    {
        $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
        ];

        return isset($mimeTypes[$ext])
            ? ['mime_type' => $mimeTypes[$ext]]
            : [];
    }

    /**
     * Generate a title from URL based on content type.
     */
    public function generateTitleFromUrl(string $url, string $type): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? 'Unknown';

        // Remove www. prefix
        $host = str_replace('www.', '', $host);

        if ($type === 'youtube') {

            $video_id = \Alaouy\Youtube\Youtube::parseVidFromURL($url);

            return Youtube::getVideoInfo($video_id)->snippet->title;
        }

        if (str_starts_with($type, 'google_')) {
            return match ($type) {
                'google_doc' => 'Google Doc',
                'google_sheet' => 'Google Sheet',
                'google_slides' => 'Google Slides',
                'google_form' => 'Google Form',
                default => 'Google Drive File',
            };
        }

        $path = trim($parsed['path'] ?? '', '/');
        $segments = explode('/', $path);
        $lastSegment = end($segments) ?: $host;

        // Convert kebab-case or snake_case to readable title
        $title = str_replace(['-', '_'], ' ', $lastSegment);
        $title = ucwords($title);

        return $title ?: $host;
    }


    public function getYoutubeVideoDescription($url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $video_id = \Alaouy\Youtube\Youtube::parseVidFromURL($url);

        return Youtube::getVideoInfo($video_id)->snippet->description;

    }
}
