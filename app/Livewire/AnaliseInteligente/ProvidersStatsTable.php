<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class ProvidersStatsTable extends TableComponent
{
    public array $rows = [];

    public function openProvider(string $provider): void
    {
        $this->dispatch('open-provider-from-table', provider: $provider);
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
                    ->action(fn ($record) => $this->openProvider($record['provider'])),

                TextColumn::make('occurrences')->label('Ocorrências')->numeric()->sortable(),
                TextColumn::make('unique_ips')->label('IPs únicos')->numeric()->sortable(),
                //TextColumn::make('cities')->label('Cidades')->numeric()->sortable(),
                //TextColumn::make('mobile_occurrences')->label('Ocorr. móveis')->numeric()->sortable(),
                //TextColumn::make('mobile_percent')->label('% móvel')->suffix('%')->numeric()->sortable(),
                TextColumn::make('last_seen')->label('Último acesso')->sortable(),
            ])
            ->defaultSort('occurrences', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
