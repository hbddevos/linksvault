<?php

namespace App\Models;

use App\Concerns\AddTeamId;
use App\Concerns\AddUserId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelDaily\FilaTeams\Models\Team;

class Tag extends Model
{
    use AddTeamId, AddUserId;

    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'slug',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
