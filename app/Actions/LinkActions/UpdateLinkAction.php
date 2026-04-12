<?php

namespace App\Actions\LinkActions;

use App\Models\Link;

class UpdateLinkAction
{
    public static function execute(Link $link, array $data): Link
    {
        $link->update([
            ...$data,
            'url_hash' => isset($data['url']) ? hash('sha256', $data['url']) : $link->url_hash,
        ]);

        return $link->refresh();
    }
}
