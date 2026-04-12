<?php

namespace App\Actions\CategoryActions;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateCategoryAction
{
    public static function execute(array $data): Category
    {
        return Category::create([
            ...$data,
            // 'user_id' => Auth::id(),
            // 'team_id' => Auth::user()->current_team_id,
            'slug' => $data['slug'] ?? Str::slug($data['name']),
        ]);
    }
}
