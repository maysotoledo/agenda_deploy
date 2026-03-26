<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class TimelineTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->rows))
            ->columns([
                TextColumn::make('datetime')->label('Data/Hora')->sortable()->searchable(),
                TextColumn::make('ip')->label('IP')->fontFamily('mono')->searchable(),
                TextColumn::make('provider')->label('Provedor')->searchable(),
                TextColumn::make('city')->label('Cidade')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
            ])
            ->defaultSort('datetime', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
