<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TagForm
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
            MarkdownEditor::make('description')
                ->label(__('Description'))
                ->columnSpanFull()
                ->maxLength(255),
        ];
    }
}
