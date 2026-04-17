<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelDaily\FilaTeams\Models\Membership;
use LaravelDaily\FilaTeams\Models\TeamInvitation;
use Symfony\Component\HttpFoundation\Response;

class AcceptInvitationController extends Controller
{
    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $invitation = TeamInvitation::where('code', $code)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect(Filament::getLoginUrl())
                ->with('error', __('filateams::filateams.flash.invitation_expired'));
        }

        $user = $request->user();

        // Not logged in — redirect to login with return URL
        if (! $user) {
            session()->put('url.intended', $request->fullUrl());

            return redirect(Filament::getLoginUrl());
        }

        // Verify the authenticated user's email matches the invitation
        if ($user->email !== $invitation->email) {
            abort(Response::HTTP_FORBIDDEN, __('filateams.flash.invitation_wrong_email'));
        }

        // Already a member — just mark accepted and redirect
        if ($user->belongsToTeam($invitation->team)) {
            $invitation->update(['accepted_at' => now()]);

            return redirect(Filament::getUrl($invitation->team));
        }

        // Accept the invitation
        Membership::firstOrCreate(
            [
                'team_id' => $invitation->team_id,
                'user_id' => $user->id,
            ],
            [
                'role' => $invitation->role->value,
            ]
        );

        $invitation->update(['accepted_at' => now()]);

        $user->switchTeam($invitation->team);

        return redirect(Filament::getUrl($invitation->team));
    }
}
