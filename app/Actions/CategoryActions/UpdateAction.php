<?php

namespace App\Actions\CategoryActions;

use App\Models\Category;
use Illuminate\Support\Str;

class UpdateAction
{
    public static function execute(Category $category, array $data): Category
    {
        $category->update([
            ...$data,
            'slug' => isset($data['name']) ? Str::slug($data['name']) : ($data['slug'] ?? $category->slug),
        ]);

        return $category->refresh();
    }
}
