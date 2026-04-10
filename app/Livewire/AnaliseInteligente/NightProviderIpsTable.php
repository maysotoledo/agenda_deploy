<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Support\Collection;

class NightProviderIpsTable extends TableComponent
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

                TextColumn::make('count')
                    ->label('Ocorrências')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_seen')
                    ->label('Última vez')
                    ->sortable(),

                TextColumn::make('city')
                    ->label('Cidade')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
            ])
            ->defaultSort('count', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
