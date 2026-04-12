<?php

namespace App\Actions\LinkActions;

use App\Models\Link;
use Illuminate\Support\Facades\Auth;

class CreateLinkAction
{
    public static function execute(array $data): Link
    {
        return Link::create([
            ...$data,
            // 'user_id' => Auth::id(),
            // 'team_id' => Auth::user()->current_team_id,
            'url_hash' => hash('sha256', $data['url']),
        ]);
    }
}
