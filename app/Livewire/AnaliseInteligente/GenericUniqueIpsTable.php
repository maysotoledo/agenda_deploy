<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Support\Collection;

class GenericUniqueIpsTable extends TableComponent
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
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('provider')
                    ->label('Operadora/ISP')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('city')
                    ->label('Cidade')
                    ->searchable(),

                TextColumn::make('connection_type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),

                TextColumn::make('count')
                    ->label('Ocorrências')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_seen_utc')
                    ->label('Último (UTC)')
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('count', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
