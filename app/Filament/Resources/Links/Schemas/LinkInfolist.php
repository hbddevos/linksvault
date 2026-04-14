<?php

namespace App\Filament\Resources\Links\Schemas;

use App\Filament\Resources\Links\Actions\ShareLinkModalAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LinkInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->components([
                        // Title header
                        Group::make([
                            TextEntry::make('title')
                                ->size('2xl')
                                ->weight('bold')
                                ->color('primary'),
                        ])->columnSpanFull(),

                        // URL with copy button
                        Group::make([
                            TextEntry::make('url')
                                ->label('')
                                ->url(fn ($record) => $record->url, shouldOpenInNewTab: true)
                                ->copyable()
                                ->copyMessage(__('URL copied to clipboard'))
                                ->color('gray')
                                ->size('sm'),
                        ])->columnSpanFull(),

                        // Meta info (date added, visits)
                        Group::make([
                            TextEntry::make('created_at')
                                ->label(__('Added'))
                                ->since()
                                ->color('gray')
                                ->size('sm'),
                            TextEntry::make('visit_count')
                                ->label(__('Visits'))
                                ->numeric()
                                ->color('gray')
                                ->size('sm'),
                        ])->columns(2)->columnSpanFull(),

                        // Category
                        TextEntry::make('category.name')
                            ->label(__('Category'))
                            ->badge()
                            ->color(fn ($state) => $state ? 'primary' : 'gray')
                            ->placeholder('—'),

                        // Tags - Improved display
                        RepeatableEntry::make('tags')
                            ->label(__('Tags'))
                            ->schema([
                                TextEntry::make('name')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-m-tag'),
                            ])
                            ->contained(false)
                            ->grid(3)
                            ->visible(fn ($record) => $record->tags && $record->tags->count() > 0)
                            ->placeholder('—'),

                        // Divider
                        Group::make([])
                            ->extraAttributes(['class' => 'border-t border-gray-200 dark:border-gray-700 my-4']),

                        // Description section
                        TextEntry::make('description')
                            ->label(__('Description'))
                            ->markdown()
                            ->placeholder(__('No description provided.'))
                            ->columnSpanFull(),

                        // Objective
                        TextEntry::make('objective')
                            ->label(__('Objective'))
                            ->placeholder(__('No objective set.')),

                        // Divider
                        Group::make([])
                            ->extraAttributes(['class' => 'border-t border-gray-200 dark:border-gray-700 my-4']),

                        // Actions section
                        Section::make([
                            Actions::make([
                                ShareLinkModalAction::make()
                                    ->label(__('Partager'))
                                    ->icon('heroicon-o-share'),
                                Action::make('toggle_favorite')
                                    ->label(fn ($record) => $record->is_favorite ? __('Remove from favorites') : __('Add to favorites'))
                                    ->icon(fn ($record) => $record->is_favorite ? 'heroicon-s-star' : 'heroicon-o-star')
                                    ->color(fn ($record) => $record->is_favorite ? 'warning' : 'gray'),
                                Action::make('archive')
                                    ->label(__('Archive'))
                                    ->icon('heroicon-o-archive-box')
                                    ->color('gray'),
                                EditAction::make()
                                    ->label(__('Edit'))
                                    ->icon('heroicon-o-pencil'),
                                DeleteAction::make()
                                    ->label(__('Delete'))
                                    ->icon('heroicon-o-trash')
                                    ->color('danger'),
                            ])->columns(3),
                        ])->heading(__('Actions')),
                    ]),
            ]);
    }
}
