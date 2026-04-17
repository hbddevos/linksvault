<?php

declare(strict_types=1);

namespace App\Enums;

use LaravelDaily\FilaTeams\Contracts\TeamPermissionContract;

enum TeamPermission: string implements TeamPermissionContract
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';
}
