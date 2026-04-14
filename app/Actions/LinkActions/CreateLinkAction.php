<?php

namespace App\Actions\LinkActions;

use App\Models\Link;
use App\Services\ContentDetectionService;
use Illuminate\Support\Facades\Auth;

class CreateLinkAction
{
    public static function execute(array $data): Link
    {
        $contentDetection = app(ContentDetectionService::class);
        $analysis = $contentDetection->analyze($data['url']);

        // Merge detected metadata with any existing metadata
        $metadata = array_merge(
            $data['metadata'] ?? [],
            $analysis['metadata']
        );

        // Auto-generate title from URL if not provided
        $title = $data['title'] ?? null;
        if (empty($title)) {
            $title = $contentDetection->generateTitleFromUrl($data['url'], $analysis['type']);
        }

        return Link::create([
            ...$data,
            'title' => $title,
            // 'user_id' => Auth::id(),
            // 'team_id' => Auth::user()->current_team_id,
            'url_hash' => hash('sha256', $data['url']),
            'content_type' => $analysis['type'],
            'metadata' => $metadata,
        ]);
    }
}
