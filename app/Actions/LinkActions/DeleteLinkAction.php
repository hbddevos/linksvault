<?php

namespace App\Actions\LinkActions;

use App\Models\Link;

class DeleteLinkAction
{
    public static function execute(Link $link): bool
    {
        return $link->delete();
    }

    public static function executeMultiple(array $links): void
    {
        foreach ($links as $link) {
            $link->delete();
        }
    }
}
