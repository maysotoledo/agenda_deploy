<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Support\Collection;

class NightProvidersTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->rows))
            ->columns([
                TextColumn::make('provider')
                    ->label('Provedor')
                    ->searchable()
                    ->wrap()
                    ->action(function ($record) {
                        $this->dispatch('open-night-provider-events', provider: $record['provider']);
                    })
                    ->color('primary'),

                TextColumn::make('count')
                    ->label('Noturnos')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_seen')
                    ->label('Última ocorrência')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('connection_type')
                    ->label('Tipo')
                    ->badge(),
            ])
            ->defaultSort('count', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
