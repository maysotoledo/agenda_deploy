<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class CitiesStatsTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->rows))
            ->columns([
                TextColumn::make('city')->label('Cidade')->searchable()->sortable(),
                TextColumn::make('occurrences')->label('Ocorrências')->numeric()->sortable(),
                TextColumn::make('unique_ips')->label('IPs únicos')->numeric()->sortable(),
                TextColumn::make('providers')->label('Provedores')->numeric()->sortable(),
                TextColumn::make('mobile_occurrences')->label('Ocorr. móveis')->numeric()->sortable(),
                TextColumn::make('mobile_percent')->label('% móvel')->suffix('%')->numeric()->sortable(),
                TextColumn::make('last_seen')->label('Última vez visto')->sortable(),
            ])
            ->defaultSort('occurrences', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
