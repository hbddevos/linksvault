<?php

namespace App\Actions\TagActions;

use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateTagAction
{
    public static function execute(array $data): Tag
    {
        return Tag::create([
            ...$data,
            // 'user_id' => Auth::id(),
            // 'team_id' => Auth::user()->current_team_id,
            'slug' => $data['slug'] ?? Str::slug($data['name']),
        ]);
    }
}
