<?php

declare(strict_types=1);

namespace App\Support;

readonly class TeamPermissions
{
    public function __construct(
        public bool $canUpdateTeam = false,
        public bool $canDeleteTeam = false,
        public bool $canAddMember = false,
        public bool $canUpdateMember = false,
        public bool $canRemoveMember = false,
        public bool $canCreateInvitation = false,
        public bool $canCancelInvitation = false,
    ) {}
}
