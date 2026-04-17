<?php

declare(strict_types=1);

namespace App\Contracts;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;

interface HasTeamMembership extends FilamentUser, HasTenants {}
