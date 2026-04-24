<?php

namespace App\Livewire\AnaliseInteligente;

use App\Models\AnaliseRunIp;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;

class GoogleCitiesTable extends TableComponent
{
    public int $runId;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AnaliseRunIp::query()
                    ->leftJoin('ip_enrichments', 'ip_enrichments.ip', '=', 'analise_run_ips.ip')
                    ->where('analise_run_id', $this->runId)
                    ->selectRaw("
                        MIN(analise_run_ips.id) as id,
                        COALESCE(NULLIF(ip_enrichments.city, ''), 'Desconhecida') as city,
                        SUM(analise_run_ips.occurrences) as occurrences,
                        COUNT(*) as unique_ips,
                        COUNT(DISTINCT COALESCE(NULLIF(ip_enrichments.isp, ''), NULLIF(ip_enrichments.org, ''), 'Desconhecido')) as providers,
                        SUM(CASE WHEN ip_enrichments.mobile = 1 THEN analise_run_ips.occurrences ELSE 0 END) as mobile_occurrences,
                        MAX(analise_run_ips.last_seen_at) as last_seen_at
                    ")
                    ->groupBy('city')
            )
            ->columns([
                TextColumn::make('city')
                    ->label('Cidade')
                    ->wrap(),

                TextColumn::make('occurrences')
                    ->label('Ocorrencias')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('unique_ips')
                    ->label('IPs unicos')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('providers')
                    ->label('Provedores')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('mobile_occurrences')
                    ->label('Ocorr. movel')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('mobile_percent')
                    ->label('% movel')
                    ->state(fn ($record): float => (float) ($record->occurrences > 0 ? round(($record->mobile_occurrences / $record->occurrences) * 100, 2) : 0)),

                TextColumn::make('last_seen_at')
                    ->label('Ultimo (GMT-3)')
                    ->formatStateUsing(fn ($state): ?string => $state?->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s'))
                    ->sortable(),
            ])
            ->defaultSort('occurrences', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public function render()
    {
        return view('livewire.analise-inteligente.google-cities-table');
    }

}
