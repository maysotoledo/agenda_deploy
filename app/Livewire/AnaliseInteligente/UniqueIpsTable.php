<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;

class UniqueIpsTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->records(fn (?string $search): LengthAwarePaginatorContract => $this->buildPaginator($search))
            ->columns([
                TextColumn::make('ip')->label('IP')->fontFamily('mono')->searchable(),
                TextColumn::make('count')->label('Ocorrências')->numeric()->sortable(),
                TextColumn::make('last_seen')->label('Última vez visto')->sortable()->searchable(),
                TextColumn::make('provider')->label('Provedor')->searchable(),
                TextColumn::make('city')->label('Cidade')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
            ])
            ->defaultSort('count', 'desc')
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
                $ip = mb_strtolower((string) data_get($row, 'ip', ''));
                $provider = mb_strtolower((string) data_get($row, 'provider', ''));
                $city = mb_strtolower((string) data_get($row, 'city', ''));
                $type = mb_strtolower((string) data_get($row, 'type', ''));
                $lastSeen = mb_strtolower((string) data_get($row, 'last_seen', ''));

                return str_contains($ip, $needle)
                    || str_contains($provider, $needle)
                    || str_contains($city, $needle)
                    || str_contains($type, $needle)
                    || str_contains($lastSeen, $needle);
            }));
        }

        // ordenação padrão: count desc
        usort($filtered, function ($a, $b) {
            $ca = (int) data_get($a, 'count', 0);
            $cb = (int) data_get($b, 'count', 0);

            if ($cb !== $ca) {
                return $cb <=> $ca;
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
