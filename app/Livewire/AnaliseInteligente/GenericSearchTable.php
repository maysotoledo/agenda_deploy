<?php

namespace App\Livewire\AnaliseInteligente;

use App\Models\AnaliseRunEvent;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Component;

class GenericSearchTable extends Component implements HasTable
{
    use InteractsWithTable;

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
        return view('livewire.analise-inteligente.generic-search-table');
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }
}
