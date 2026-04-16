<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use LaravelDaily\FilaTeams\Models\Team;
use LaravelDaily\FilaTeams\Models\TeamInvitation;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTeamInvitation implements ValidationRule
{
    public function __construct(
        protected Team $team,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->team->members()->where('email', $value)->exists()) {
            $fail(__('filateams::filateams.validation.invitation.already_member'));

            return;
        }

        $hasPendingInvitation = TeamInvitation::where('team_id', $this->team->id)
            ->where('email', $value)
            ->whereNull('accepted_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($hasPendingInvitation) {
            $fail(__('filateams::filateams.validation.invitation.pending_exists'));
        }
    }
}
