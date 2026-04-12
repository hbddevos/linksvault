<?php

namespace App\Actions\CategoryActions;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class ListCategoriesAction
{
    public static function execute(array $filters = [], string $orderBy = 'sort_order', string $orderDirection = 'asc'): Collection
    {
        $query = Category::query();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy($orderBy, $orderDirection)->get();
    }
}
