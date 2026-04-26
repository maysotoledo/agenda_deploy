<?php

namespace App\Livewire\AnaliseInteligente;

use App\Models\AnaliseRunIp;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;

class GenericUniqueIpsTable extends TableComponent
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
                        analise_run_ips.id,
                        analise_run_ips.ip,
                        analise_run_ips.occurrences as count,
                        analise_run_ips.last_seen_at,
                        COALESCE(NULLIF(ip_enrichments.isp, ''), NULLIF(ip_enrichments.org, ''), 'Desconhecido') as provider,
                        COALESCE(NULLIF(ip_enrichments.city, ''), 'Desconhecida') as city,
                        CASE WHEN ip_enrichments.mobile = 1 THEN 'Movel' ELSE 'Residencial' END as connection_type
                    ")
            )
            ->columns([
                TextColumn::make('ip')
                    ->label('IP')
                    ->fontFamily('mono')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('provider')
                    ->label('Operadora/ISP')
                    ->wrap(),

                TextColumn::make('city')
                    ->label('Cidade'),

                TextColumn::make('connection_type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('count')
                    ->label('Ocorrencias')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('last_seen_at')
                    ->label('Ultimo (GMT-3)')
                    ->formatStateUsing(fn ($state): ?string => $state?->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s'))
                    ->sortable(),
            ])
            ->defaultSort('count', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    public function render()
    {
        return view('livewire.analise-inteligente.generic-unique-ips-table');
    }
}
