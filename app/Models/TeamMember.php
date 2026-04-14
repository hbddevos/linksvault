<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    // protected $fillable = [
    //     'team_id',
    //     'user_id',
    //     'role',
    // ];

    // public function team(): BelongsTo
    // {
    //     return $this->belongsTo(Team::class);
    // }

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
