<?php

namespace App\Models;

use App\Enums\TeamRole;
use Database\Factories\TeamInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeamInvitation extends Model
{
     /** @use HasFactory<TeamInvitationFactory> */
    use HasFactory;

    protected $table = 'team_invitations';

    protected $fillable = [
        'team_id',
        'email',
        'role',
        'code',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    protected static function newFactory(): TeamInvitationFactory
    {
        return TeamInvitationFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $invitation): void {
            $invitation->code = Str::random(64);
        });
    }

    protected function casts(): array
    {
        return [
            'role'        => config('filateams.enums.role', TeamRole::class),
            'expires_at'  => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
