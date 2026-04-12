<?php

namespace App\Actions\CategoryActions;

use App\Models\Category;

class DeleteCategoryAction
{
    public static function execute(Category $category): bool
    {
        return $category->delete();
    }

    public static function executeMultiple(array $categories): void
    {
        foreach ($categories as $category) {
            $category->delete();
        }
    }
}
