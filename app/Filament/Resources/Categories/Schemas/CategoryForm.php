<?php

namespace App\Filament\Resources\Categories\Schemas;

use FawazIwalewa\FilamentIconPicker\Forms\Components\IconPicker;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->components(self::getComponents()),
            ]);
    }

    public static function getComponents(): array
    {
        return [
            Grid::make(2)
                ->components([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->maxLength(255)
                        ->autofocus()
                        ->live(onBlur: false, debounce: '50ms')
                        ->afterStateUpdated(function ($state, Set $set) {
                            $set('slug', Str::slug($state));
                        }),
                    TextInput::make('slug')
                        ->label(__('Slug'))
                        ->required()
                        ->maxLength(255)
                        ->readOnly(),
                ]),
            Grid::make(3)
                ->components([
                    ColorPicker::make('color')
                        ->label(__('Color'))
                        ->required()
                        ->default('#6B7280'),
                    IconPicker::make('icon')
                        ->label(__('Icon'))
                        // ->searchable()
                        ->sets(['heroicons']),
                    TextInput::make('sort_order')
                        ->label(__('Sort Order'))
                        ->default(0)
                        ->maxLength(255)
                        ->numeric(),
                ]),

            MarkdownEditor::make('description')
                ->label(__('Description'))
                ->columnSpanFull()
                ->maxLength(255),
        ];
    }
}
