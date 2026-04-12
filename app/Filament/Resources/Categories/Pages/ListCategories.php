<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Actions\CategoryActions\CreateCategoryAction;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->form(CategoryForm::getComponents())
                ->label(__('Create Category'))
                ->icon(TablerIcon::Plus)
                ->action(function (array $data) {
                    CreateCategoryAction::execute($data);
                }),
        ];
    }

    public function notifications(): void
    {
        Notification::make()
            ->title(__('Category created successfully'))
            ->success()
            ->send();
    }
}
