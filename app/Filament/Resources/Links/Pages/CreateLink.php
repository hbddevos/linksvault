<?php

namespace App\Filament\Resources\Links\Pages;

use App\Filament\Resources\Links\LinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLink extends CreateRecord
{
    protected static string $resource = LinkResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // Clean up tags input
    //     return $data;
    // }
}
