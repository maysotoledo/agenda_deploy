<?php

namespace App\Livewire\AnaliseInteligente;

use App\Models\AnaliseRunEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;

class GoogleSearchTable extends TableComponent
{
    public int $runId;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AnaliseRunEvent::query()
                    ->where('analise_run_id', $this->runId)
                    ->where('event_type', 'search')
            )
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Data (GMT-3)')
                    ->formatStateUsing(fn ($state): ?string => $state?->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s'))
                    ->sortable(),

                TextColumn::make('target')
                    ->label('Pesquisa')
                    ->wrap()
                    ->searchable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10);
    }

    public function render()
    {
        return view('livewire.analise-inteligente.google-search-table');
    }

}
