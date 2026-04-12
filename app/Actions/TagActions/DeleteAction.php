<?php

namespace App\Actions\TagActions;

use App\Models\Tag;

class DeleteAction
{
    public static function execute(Tag $tag): bool
    {
        return $tag->delete();
    }

    public static function executeMultiple(array $tags): void
    {
        foreach ($tags as $tag) {
            $tag->delete();
        }
    }
}
