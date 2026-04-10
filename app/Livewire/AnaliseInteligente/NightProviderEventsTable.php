<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Support\Collection;

class NightEventsTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->rows))
            ->columns([
                TextColumn::make('ip')
                    ->label('IP')
                    ->fontFamily('mono')
                    ->searchable(),

                TextColumn::make('datetime')
                    ->label('Data/Hora')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('provider')
                    ->label('Provedor')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('city')
                    ->label('Cidade')
                    ->searchable(),

                TextColumn::make('connection_type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('period')
                    ->label('Faixa')
                    ->badge()
                    ->default('23h–06h'),
            ])
            ->defaultSort('datetime', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
