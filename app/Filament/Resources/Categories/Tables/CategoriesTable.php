<?php

namespace App\Filament\Resources\Categories\Tables;

use Daljo25\FilamentTablerIcons\Enums\TablerIcon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()
                    ->sortable(),
                ColorColumn::make('color')
                    ->label(__('Color'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('icon')
                    ->icon(fn ($state) => $state ?? TablerIcon::AccessPoint)
                    ->label(__('Icon'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label(__('Sort Order'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('name'),

            ], FiltersLayout::AboveContent)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
