<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Support\Collection;

class NightProvidersStatsTable extends TableComponent
{
    public array $rows = [];

    public function openNightProvider(string $provider): void
    {
        $this->dispatch('open-night-provider-events', provider: $provider);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->rows))
            ->columns([
                TextColumn::make('provider')
                    ->label('Provedor')
                    ->searchable()
                    ->sortable()
                    ->action(fn ($record) => $this->openNightProvider($record['provider'])),

                TextColumn::make('occurrences')
                    ->label('Ocorrências (noturno)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('unique_ips')
                    ->label('IPs únicos (noturno)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_seen')
                    ->label('Última ocorrência')
                    ->sortable(),
            ])
            ->defaultSort('occurrences', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
