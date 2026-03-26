<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class UniqueIpsTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->rows))
            ->columns([
                TextColumn::make('ip')->label('IP')->fontFamily('mono')->searchable(),
                TextColumn::make('count')->label('Ocorrências')->numeric()->sortable(),
                TextColumn::make('last_seen')->label('Última vez visto')->sortable()->searchable(),
                TextColumn::make('provider')->label('Provedor')->searchable(),
                TextColumn::make('city')->label('Cidade')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
            ])
            ->defaultSort('count', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
