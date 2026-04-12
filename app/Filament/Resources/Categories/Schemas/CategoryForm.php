<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }



    public static function getComponents(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label(__('Name'))
                ->required()
                ->maxLength(255)
                ->autofocus()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('slug', Str::slug($state));
                }),
            Forms\Components\TextInput::make('slug')
                ->label(__('Slug'))
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\ColorPicker::make('color')
                ->label(__('Color'))
                ->required()
                ->default('#6B7280'),
            Forms\Components\TextInput::make('icon')
                ->label(__('Icon'))
                ->required()
                ->maxLength(50),
            Forms\Components\Textarea::make('description')
                ->label(__('Description'))
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('sort_order')
                ->label(__('Sort Order'))
                ->required()
                ->maxLength(255),
        ];
    }
}
