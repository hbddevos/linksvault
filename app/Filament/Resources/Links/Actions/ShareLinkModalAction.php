<?php

namespace App\Filament\Resources\Links\Actions;

use App\Actions\LinkShareActions\ShareLinkAction as ShareLinkBusinessAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ShareLinkModalAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'share_link';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Partager'))
            ->icon('heroicon-o-share')
            ->color('primary')
            ->modalHeading(__('Partager ce lien'))
            ->modalWidth('2xl')
            ->form([
                Fieldset::make('Destinataires')
                    ->schema([
                        Repeater::make('recipients')
                            ->label('')
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('Utilisateur inscrit (optionnel)'))
                                    ->options(fn () => User::where('id', '!=', Auth::id())
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $user = User::find($state);
                                            if ($user) {
                                                $set('email', $user->email);
                                                $set('name', $user->name);
                                            }
                                        }
                                    })
                                    ->placeholder('Sélectionner un utilisateur...'),
                                
                                TextInput::make('email')
                                    ->label(__('Email'))
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                
                                TextInput::make('name')
                                    ->label(__('Nom (optionnel)'))
                                    ->maxLength(255),
                            ])
                            ->columns(3)
                            ->minItems(1)
                            ->maxItems(10)
                            ->default([
                                ['email' => '', 'name' => '']
                            ])
                            ->addActionLabel(__('Ajouter un destinataire')),
                    ]),

                Fieldset::make('Message')
                    ->schema([
                        Textarea::make('personal_message')
                            ->label(__('Message personnel (optionnel)'))
                            ->rows(4)
                            ->maxLength(500)
                            ->helperText(__('Ajoutez un message personnalisé pour accompagner votre partage.')),
                    ]),

                Fieldset::make('Options')
                    ->schema([
                        Checkbox::make('set_expiration')
                            ->label(__('Définir une date d\'expiration'))
                            ->live(),
                        
                        TextInput::make('expires_in_days')
                            ->label(__('Expirer après (jours)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->default(7)
                            ->visible(fn (callable $get) => $get('set_expiration'))
                            ->helperText(__('Laissez vide pour aucune expiration.')),
                    ]),
            ])
            ->action(function (array $data, $record) {
                $shareAction = app(ShareLinkBusinessAction::class);
                
                // Nettoyer les destinataires (supprimer les entrées vides)
                $recipients = collect($data['recipients'])
                    ->filter(fn ($r) => !empty($r['email']))
                    ->map(fn ($r) => [
                        'email' => $r['email'],
                        'user_id' => $r['user_id'] ?? null,
                        'name' => $r['name'] ?? null,
                    ])
                    ->values()
                    ->toArray();

                if (empty($recipients)) {
                    Notification::make()
                        ->title(__('Erreur'))
                        ->body(__('Veuillez ajouter au moins un destinataire valide.'))
                        ->danger()
                        ->send();
                    
                    return;
                }

                $expiresInDays = $data['set_expiration'] && !empty($data['expires_in_days'])
                    ? (int) $data['expires_in_days']
                    : null;

                $result = $shareAction->execute(
                    link: $record,
                    sender: Auth::user(),
                    recipients: $recipients,
                    personalMessage: $data['personal_message'] ?? null,
                    expiresInDays: $expiresInDays
                );

                if ($result['success']) {
                    $count = count($result['shares']);
                    
                    Notification::make()
                        ->title(__('Succès'))
                        ->body(__(":count lien(s) partagé(s) avec succès!", ['count' => $count]))
                        ->success()
                        ->send();
                } else {
                    $errorCount = count($result['errors']);
                    $successCount = count($result['shares']);
                    
                    Notification::make()
                        ->title(__('Partage partiel'))
                        ->body(__(":success réussi(s), :error échec(s). Vérifiez les logs pour plus de détails.", [
                            'success' => $successCount,
                            'error' => $errorCount,
                        ]))
                        ->warning()
                        ->send();
                }
            });
    }
}
