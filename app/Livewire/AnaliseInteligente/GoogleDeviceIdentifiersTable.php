<?php

namespace App\Livewire\AnaliseInteligente;

use App\Models\AnaliseRunEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;

class GoogleDeviceIdentifiersTable extends TableComponent
{
    public int $runId;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AnaliseRunEvent::query()
                    ->where('analise_run_id', $this->runId)
                    ->where('event_type', 'access')
                    ->whereNotNull('device_identifier_value')
                    ->selectRaw('MIN(id) as id, device_identifier_type as type, device_identifier_value as value, COUNT(*) as occurrences, MAX(occurred_at) as occurred_at')
                    ->groupBy('device_identifier_type', 'device_identifier_value')
            )
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('value')
                    ->label('Identificador')
                    ->fontFamily('mono')
                    ->wrap(),

                TextColumn::make('occurrences')
                    ->label('Ocorrencias')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('occurred_at')
                    ->label('Ultimo (GMT-3)')
                    ->formatStateUsing(fn ($state): ?string => $state?->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s'))
                    ->sortable(),
            ])
            ->defaultSort('occurrences', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    public function render()
    {
        return view('livewire.analise-inteligente.google-device-identifiers-table');
    }

}
