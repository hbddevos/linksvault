<?php

namespace App\Filament\Resources\Tags\Pages;

use App\Actions\TagActions\CreateTagAction;
use App\Filament\Resources\Tags\Schemas\TagForm;
use App\Filament\Resources\Tags\TagResource;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->form(TagForm::getComponents())
                ->label(__('Create Tag'))
                ->icon(TablerIcon::Plus)
                ->action(function (array $data) {
                    CreateTagAction::execute($data);
                }),
        ];
    }

    public function notifications(): void
    {
        Notification::make()
            ->title(__('Tag created successfully'))
            ->success()
            ->send();
    }
}
