<?php

namespace App\Models;

use App\Concerns\AddTeamId;
use App\Concerns\AddUserId;
use App\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Link extends Model
{
    use AddTeamId, AddUserId, BelongsToTeam;

    protected $fillable = [
        'user_id',
        'team_id',
        'url',
        'url_hash',
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
}
