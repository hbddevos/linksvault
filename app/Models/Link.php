<?php

namespace App\Models;

use App\Concerns\AddUserId;
use App\Enums\ContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Link extends Model
{
    use AddUserId;

    protected $fillable = [
        'user_id',
        'team_id',
        'url',
        'url_hash',
        'tags',
        'title',
        'description',
        'content_type',
        'metadata',
        'ai_summary',
        'ai_summary_status',
        'objective',
        'category_id',
        'favicon_url',
        'thumbnail_url',
        'is_favorite',
        'is_archived',
        'visit_count',
        'last_visited_at',
    ];

    protected $casts = [
        'url' => 'string',
        'content_type' => ContentType::class,
        'metadata' => 'array',
        'is_favorite' => 'boolean',
        'is_archived' => 'boolean',
        'visit_count' => 'integer',
        'last_visited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Les partages de ce lien.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(LinkShare::class);
    }

    public function getYoutubeVideoId(): ?string
    {
        if (empty($this->url)) {
            return null;
        }

        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';

        if (preg_match($pattern, $this->url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Génère l'URL d'intégration (embed) pour l'iframe.
     */
    public function getYoutubeEmbedUrl(): ?string
    {
        $id = $this->getYoutubeVideoId();

        return $id ? "https://www.youtube.com/embed/{$id}" : null;
    }

    public function getYoutubeThumbnailUrl(): ?string
    {
        $id = $this->getYoutubeVideoId();

        return $id ? "https://img.youtube.com/vi/{$id}/0.jpg" : null;
    }
}
