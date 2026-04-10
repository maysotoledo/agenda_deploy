<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Support\Collection;

class GenericProvidersTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->rows))
            ->columns([
                TextColumn::make('provider')
                    ->label('Operadora/ISP')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('occurrences')
                    ->label('Ocorrências')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('unique_ips')
                    ->label('IPs únicos')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('cities')
                    ->label('Cidades')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('mobile_occurrences')
                    ->label('Ocorr. móvel')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('mobile_percent')
                    ->label('% móvel')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_seen_utc')
                    ->label('Último (UTC)')
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('occurrences', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
