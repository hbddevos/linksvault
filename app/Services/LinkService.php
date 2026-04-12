<?php

namespace App\Services;

use App\Models\Link;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LinkService
{
    /**
     * Create a new link for a user.
     *
     * @param  User  $user  The user who owns the link
     * @param  array{url: string, title?: string|null, description?: string|null, category_id?: int|null, objective?: string|null, tags?: array<int, string>}  $data
     */
    public function create(User $user, array $data): Link
    {
        $url = $data['url'];
        $urlHash = hash('sha256', $url);

        $existing = Link::where('user_id', $user->id)
            ->where('url_hash', $urlHash)
            ->first();

        if ($existing) {
            throw new \InvalidArgumentException(
                'This URL already exists in your vault. Duplicate detected.'
            );
        }

        $link = $user->links()->create([
            'url' => $url,
            'url_hash' => $urlHash,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'content_type' => 'other',
            'category_id' => $data['category_id'] ?? null,
            'objective' => $data['objective'] ?? null,
            'metadata' => [],
            'ai_summary_status' => 'pending',
        ]);

        if (! empty($data['tags'])) {
            $link->tags()->attach($data['tags']);
        }

        return $link->load('tags', 'category');
    }

    /**
     * Update an existing link.
     *
     * @param  Link  $link  The link to update
     * @param  array{title?: string|null, description?: string|null, category_id?: int|null, objective?: string|null, is_favorite?: bool, is_archived?: bool, tags?: array<int, string>}  $data
     */
    public function update(Link $link, array $data): Link
    {
        $fillable = [
            'title',
            'description',
            'category_id',
            'objective',
            'is_favorite',
            'is_archived',
            'content_type',
            'metadata',
            'favicon_url',
            'thumbnail_url',
            'ai_summary',
            'ai_summary_status',
        ];

        $updateData = [];
        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        $link->update($updateData);

        if (isset($data['tags'])) {
            $link->tags()->sync($data['tags']);
        }

        return $link->fresh(['tags', 'category']);
    }

    /**
     * Delete a link and detach all tags.
     */
    public function delete(Link $link): bool
    {
        $link->tags()->detach();

        return $link->delete();
    }

    /**
     * Get paginated links for a user with optional filters.
     *
     * @param  User  $user  The user whose links to fetch
     * @param  array{content_type?: string|null, category_id?: int|null, is_favorite?: bool|null, is_archived?: bool|null, tag?: string|null, sort?: string|null, direction?: string}  $filters
     * @return LengthAwarePaginator<Link>
     */
    public function list(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $user->links()
            ->with(['category', 'tags']);

        if (! empty($filters['content_type'])) {
            $query->where('content_type', $filters['content_type']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_favorite'])) {
            $query->where('is_favorite', (bool) $filters['is_favorite']);
        }

        if (isset($filters['is_archived'])) {
            $query->where('is_archived', (bool) $filters['is_archived']);
        } else {
            $query->where('is_archived', false);
        }

        if (! empty($filters['tag'])) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.slug', $filters['tag']));
        }

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $allowedSorts = ['created_at', 'updated_at', 'title', 'visit_count', 'last_visited_at'];

        if (in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get links by content type for a user.
     *
     * @return Collection<int, Link>
     */
    public function getByContentType(User $user, string $contentType): Collection
    {
        return $user->links()
            ->where('content_type', $contentType)
            ->where('is_archived', false)
            ->with(['category', 'tags'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get favorite links for a user.
     *
     * @return Collection<int, Link>
     */
    public function getFavorites(User $user): Collection
    {
        return $user->favoriteLinks()
            ->with(['category', 'tags'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Record a link visit.
     */
    public function recordVisit(Link $link): Link
    {
        $link->incrementVisitCount();

        return $link;
    }

    /**
     * Toggle favorite status of a link.
     */
    public function toggleFavorite(Link $link): Link
    {
        $link->update(['is_favorite' => ! $link->is_favorite]);

        return $link;
    }

    /**
     * Bulk archive links.
     *
     * @param  Collection<int, Link>|array<int, Link>  $links
     */
    public function bulkArchive(Collection|array $links, bool $archived = true): void
    {
        $ids = collect($links)->map->id->all();
        Link::whereIn('id', $ids)->update(['is_archived' => $archived]);
    }

    /**
     * Bulk delete links.
     *
     * @param  Collection<int, Link>|array<int, Link>  $links
     */
    public function bulkDelete(Collection|array $links): void
    {
        $ids = collect($links)->map->id->all();
        Link::whereIn('id', $ids)->each(fn ($link) => $this->delete($link));
    }

    /**
     * Check if a URL already exists for a user (duplicate detection).
     */
    public function isDuplicate(User $user, string $url): bool
    {
        $urlHash = hash('sha256', $url);

        return Link::where('user_id', $user->id)
            ->where('url_hash', $urlHash)
            ->exists();
    }

    /**
     * Get link count statistics for a user.
     *
     * @return array{total: int, favorites: int, archived: int, by_type: array<string, int>}
     */
    public function getStats(User $user): array
    {
        $total = $user->links()->count();
        $favorites = $user->links()->where('is_favorite', true)->count();
        $archived = $user->links()->where('is_archived', true)->count();

        $byType = $user->links()
            ->selectRaw('content_type, COUNT(*) as count')
            ->groupBy('content_type')
            ->pluck('count', 'content_type')
            ->toArray();

        return [
            'total' => $total,
            'favorites' => $favorites,
            'archived' => $archived,
            'by_type' => $byType,
        ];
    }
}
