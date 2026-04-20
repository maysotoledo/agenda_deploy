<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;

class ProvidersStatsTable extends TableComponent
{
    public array $rows = [];

    public function openProvider(string $provider): void
    {
        $this->dispatch('open-provider-ips-modal', provider: $provider);
    }

    public function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->records(fn (?string $search): LengthAwarePaginatorContract => $this->buildPaginator($search))
            ->columns([
                TextColumn::make('provider')
                    ->label('Provedor')
                    ->searchable()
                    ->sortable()
                    ->action(fn ($record) => $this->openProvider($record['provider'])),

                TextColumn::make('occurrences')->label('Ocorrências')->numeric()->sortable(),
                TextColumn::make('unique_ips')->label('IPs únicos')->numeric()->sortable(),
                TextColumn::make('last_seen')->label('Último acesso')->sortable()->searchable(),
            ])
            ->defaultSort('occurrences', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }

    private function buildPaginator(?string $search = null): LengthAwarePaginator
    {
        $search = trim((string) $search);

        $filtered = $this->rows;

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filtered = array_values(array_filter($filtered, function ($row) use ($needle) {
                $provider = mb_strtolower((string) data_get($row, 'provider', ''));
                $lastSeen = mb_strtolower((string) data_get($row, 'last_seen', ''));

                return str_contains($provider, $needle)
                    || str_contains($lastSeen, $needle);
            }));
        }

        // ordenação padrão: occurrences desc
        usort($filtered, function ($a, $b) {
            $oa = (int) data_get($a, 'occurrences', 0);
            $ob = (int) data_get($b, 'occurrences', 0);

            if ($ob !== $oa) {
                return $ob <=> $oa;
            }

            return strcmp((string) data_get($b, 'last_seen', ''), (string) data_get($a, 'last_seen', ''));
        });

        $perPage = (int) ($this->getTableRecordsPerPage() ?: 50);
        $page = (int) ($this->getTablePage() ?: 1);

        $total = count($filtered);
        $offset = max(0, ($page - 1) * $perPage);
        $items = array_slice($filtered, $offset, $perPage);

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $this->getTablePaginationPageName(),
            ]
        );
    }
}
