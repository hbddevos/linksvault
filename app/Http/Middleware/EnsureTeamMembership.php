<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeamMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->currentTeam) {
            abort(Response::HTTP_FORBIDDEN, __('filateams::filateams.flash.no_team'));
        }

        if (! $user->belongsToTeam($user->currentTeam)) {
            $fallback = $user->fallbackTeam();
            $user->forceFill(['current_team_id' => $fallback?->id])->save();

            if (! $fallback) {
                abort(Response::HTTP_FORBIDDEN, __('filateams::filateams.flash.not_member_of_any_team'));
            }
        }

        return $next($request);
    }
}
