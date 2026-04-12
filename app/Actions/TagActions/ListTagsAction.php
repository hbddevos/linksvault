<?php

namespace App\Actions\TagActions;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

class ListTagsAction
{
    public static function execute(array $filters = [], string $orderBy = 'name', string $orderDirection = 'asc'): Collection
    {
        $query = Tag::query();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('slug', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy($orderBy, $orderDirection)->get();
    }
}
