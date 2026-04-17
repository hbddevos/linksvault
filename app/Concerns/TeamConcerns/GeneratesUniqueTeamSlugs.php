<?php

declare(strict_types=1);

namespace App\Concerns\TeamConcerns;

use Illuminate\Support\Str;

trait GeneratesUniqueTeamSlugs
{
    public static function bootGeneratesUniqueTeamSlugs(): void
    {
        static::creating(function ($model): void {
            $model->slug = static::generateUniqueSlug($model->name);
        });

        static::updating(function ($model): void {
            if ($model->isDirty('name')) {
                $model->slug = static::generateUniqueSlug($model->name, $model->id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $counter = 1;

        while (static::withTrashed()->where('slug', $slug)->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
