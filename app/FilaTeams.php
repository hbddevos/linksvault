<?php

declare(strict_types=1);

namespace App;

use App\Enums\TeamPermission as EnumsTeamPermission;
use App\Enums\TeamRole;
use LaravelDaily\FilaTeams\Contracts\TeamRoleContract;
use LaravelDaily\FilaTeams\Contracts\TeamPermissionContract;

class FilaTeams
{
    /**
     * @return class-string<TeamRoleContract>
     */
    public static function roleClass(): string
    {
        return TeamRole::class;
    }

    /**
     * @return class-string<TeamPermissionContract>
     */
    public function permissionClass(): string
    {
        return EnumsTeamPermission::class;
    }

    public static function ownerRole(): TeamRoleContract
    {
        return self::roleClass()::owner();
    }

    public static function defaultRole(): TeamRoleContract
    {
        return self::roleClass()::default();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function assignableRoles(): array
    {
        return self::roleClass()::assignable();
    }
}
