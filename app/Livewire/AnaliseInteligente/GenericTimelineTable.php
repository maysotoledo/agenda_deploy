<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class GenericTimelineTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->rows))
            ->columns([
                TextColumn::make('datetime_gmt')
                    ->label('Data/Hora (GMT)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ip')
                    ->label('IP')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('provider')
                    ->label('Operadora/ISP')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('logical_port')
                    ->label('Porta Lógica')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('action')
                    ->label('Ação')
                    ->toggleable()
                    ->searchable(),

                // TextColumn::make('description')
                //     ->label('Descrição')
                //     ->wrap()
                //     ->toggleable(),
            ])
            ->defaultSort('datetime_gmt', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
