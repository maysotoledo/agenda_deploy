<?php

namespace App\Livewire\AnaliseInteligente;

use App\Models\AnaliseRunEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;

class GoogleMapsTable extends TableComponent
{
    public int $runId;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AnaliseRunEvent::query()
                    ->where('analise_run_id', $this->runId)
                    ->where('event_type', 'map')
            )
            ->columns([
                TextColumn::make('category')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Analise')
                    ->wrap(),

                TextColumn::make('target')
                    ->label('Destino/Pesquisa')
                    ->wrap(),

                TextColumn::make('origin')
                    ->label('Origem')
                    ->fontFamily('mono')
                    ->placeholder('-')
                    ->copyable(),

                TextColumn::make('occurred_at')
                    ->label('Data (GMT-3)')
                    ->formatStateUsing(fn ($state): ?string => $state?->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s'))
                    ->sortable(),

                TextColumn::make('url')
                    ->label('Maps')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Abrir' : '-')
                    ->url(fn ($record): ?string => $record->url)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->badge()
                    ->color('success'),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public function render()
    {
        return view('livewire.analise-inteligente.google-maps-table');
    }

}
