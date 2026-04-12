<?php

namespace App\Actions\TagActions;

use App\Models\Tag;
use Illuminate\Support\Str;

class UpdateTagAction
{
    public static function execute(Tag $tag, array $data): Tag
    {
        $tag->update([
            ...$data,
            'slug' => isset($data['name']) ? Str::slug($data['name']) : ($data['slug'] ?? $tag->slug),
        ]);

        return $tag->refresh();
    }
}
