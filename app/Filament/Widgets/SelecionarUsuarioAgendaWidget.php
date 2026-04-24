<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;

class SelecionarUsuarioAgendaWidget extends Widget implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected string $view = 'filament.widgets.selecionar-usuario-agenda-widget';

    protected static ?int $sort = 1;

    public ?int $agendaUserId = null;

    public bool $hasAgendaUsers = false;

    public bool $hasSingleAgendaUser = false;

    protected function agendaSelectableUsersQuery()
    {
        return User::query()
            ->where(function ($query) {
                $query->role('epc')
                    ->orWhere(fn ($roleQuery) => $roleQuery->role('cartorio_central'));
            });
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return (bool) $user && ! $user->hasRole('epc');
    }

    public function mount(): void
    {
        $currentUser = auth()->user();
        $agendaCount = $this->agendaSelectableUsersQuery()->count();

        $this->hasAgendaUsers = $agendaCount > 0;
        $this->hasSingleAgendaUser = $agendaCount === 1;

        if (! $this->hasAgendaUsers) {
            session()->forget('agenda_user_id');
            $this->agendaUserId = null;
            $this->form->fill(['agendaUserId' => null]);

            return;
        }

        $sessionUserId = session('agenda_user_id');

        $validSessionUserId = $this->agendaSelectableUsersQuery()
            ->whereKey($sessionUserId)
            ->value('id');

        if ($validSessionUserId) {
            $this->agendaUserId = (int) $validSessionUserId;
        } elseif ($currentUser?->hasRole('cartorio_central')) {
            $this->agendaUserId = (int) $currentUser->getKey();
            session(['agenda_user_id' => $this->agendaUserId]);
            $this->dispatch('agendaUserSelected', userId: $this->agendaUserId);
        } else {
            $firstAgendaUserId = (int) $this->agendaSelectableUsersQuery()
                ->orderBy('name')
                ->value('id');

            $this->agendaUserId = $firstAgendaUserId ?: null;

            if ($this->agendaUserId) {
                session(['agenda_user_id' => $this->agendaUserId]);
                $this->dispatch('agendaUserSelected', userId: $this->agendaUserId);
            } else {
                session()->forget('agenda_user_id');
            }
        }

        $this->form->fill(['agendaUserId' => $this->agendaUserId]);
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Placeholder::make('no_agenda_users')
                ->label('')
                ->content('Nenhuma agenda de EPC ou Cartorio Central foi encontrada.')
                ->visible(fn (): bool => ! $this->hasAgendaUsers),

            Forms\Components\Placeholder::make('single_agenda_info')
                ->label('')
                ->content('Existe apenas 1 agenda disponivel. Ela foi selecionada automaticamente.')
                ->visible(fn (): bool => $this->hasSingleAgendaUser),

            Forms\Components\Select::make('agendaUserId')
                ->label('Selecionar agenda')
                ->options(fn () => $this->agendaSelectableUsersQuery()
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(function (User $user): array {
                        $label = $user->name;

                        if ($user->hasRole('cartorio_central')) {
                            $label .= ' (Cartorio Central)';
                        } elseif ($user->hasRole('epc')) {
                            $label .= ' (EPC)';
                        }

                        return [$user->id => $label];
                    })
                    ->all()
                )
                ->searchable()
                ->preload()
                ->live()
                ->selectablePlaceholder(false)
                ->visible(fn (): bool => $this->hasAgendaUsers)
                ->disabled(fn (): bool => $this->hasSingleAgendaUser)
                ->required(fn (): bool => $this->hasAgendaUsers && ! $this->hasSingleAgendaUser)
                ->afterStateUpdated(function (?int $state) {
                    $previous = $this->agendaUserId;

                    if (! $state) {
                        if ($previous) {
                            $this->form->fill(['agendaUserId' => $previous]);
                        }

                        return;
                    }

                    $isSelectableAgenda = $this->agendaSelectableUsersQuery()->whereKey($state)->exists();

                    if (! $isSelectableAgenda) {
                        if ($previous) {
                            $this->form->fill(['agendaUserId' => $previous]);
                        }

                        return;
                    }

                    $this->agendaUserId = $state;
                    session(['agenda_user_id' => $state]);
                    $this->dispatch('agendaUserSelected', userId: $state);
                }),
        ]);
    }
}
