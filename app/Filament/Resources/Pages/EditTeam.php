<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use LaravelDaily\FilaTeams\Rules\TeamName;

class EditTeam extends EditTenantProfile
{
    protected static ?string $slug = 'settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function getLabel(): string
    {
        return __('filateams::filateams.pages.edit_team.label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filateams::filateams.fields.team_name.label'))
                    ->required()
                    ->maxLength(255)
                    ->rules([new TeamName]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                Livewire::make('filateams-members-table', [
                    'teamId' => $this->tenant->id,
                ]),
                Livewire::make('filateams-invitations-table', [
                    'teamId' => $this->tenant->id,
                ])->visible(fn () => Auth::user()->hasTeamPermission($this->tenant, 'invitation:create')),
                Section::make(__('filateams::filateams.sections.delete_team.heading'))
                    ->schema([
                        Actions::make([
                            Action::make('deleteTeam')
                                ->label(__('filateams::filateams.actions.delete_team.label'))
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading(__('filateams::filateams.actions.delete_team.modal_heading'))
                                ->modalDescription(__('filateams::filateams.actions.delete_team.modal_description'))
                                ->modalSubmitActionLabel(__('filateams::filateams.actions.delete_team.modal_submit_label'))
                                ->action(fn () => $this->deleteTeam()),
                        ]),
                    ])
                    ->visible(fn () => Gate::allows('delete', $this->tenant)),
            ]);
    }

    public function deleteTeam(): void
    {
        $team = $this->tenant;

        if ($team->is_personal) {
            Notification::make()
                ->danger()
                ->title(__('filateams::filateams.notifications.cannot_delete_personal_team.title'))
                ->send();

            return;
        }

        // Switch all members to their personal or fallback team
        foreach ($team->members as $member) {
            if ($member->isCurrentTeam($team)) {
                $fallback = $member->personalTeam() ?? $member->fallbackTeam($team);
                $member->forceFill(['current_team_id' => $fallback?->id])->save();
            }
        }

        $team->invitations()->delete();
        $team->memberships()->delete();
        $team->delete();

        $this->redirect(Filament::getUrl(), navigate: FilamentView::hasSpaMode());
    }

    protected function getRedirectUrl(): ?string
    {
        return static::getUrl(tenant: $this->tenant);
    }
}
