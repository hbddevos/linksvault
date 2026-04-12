<?php

namespace App\Actions\CategoryActions;

use App\Models\Category;

class ShowCategoryAction
{
    public static function execute(int $id): Category
    {
        return Category::findOrFail($id);
    }
}
