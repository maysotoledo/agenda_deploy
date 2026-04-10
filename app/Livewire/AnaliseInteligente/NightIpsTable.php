<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class NightIpsTable extends TableComponent
{
    /**
     * Espera rows no formato:
     * [
     *   ['ip','provider','city','type','count','last_seen'],
     *   ...
     * ]
     */
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
                    ->label('Ocorrências (noturno)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_seen')
                    ->label('Última vez (noturno)')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('provider')
                    ->label('Provedor')
                    ->searchable(),

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
