<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;

class NightEventsTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->records(fn (?string $search): LengthAwarePaginatorContract => $this->buildPaginator($search))
            ->columns([
                TextColumn::make('ip')->label('IP')->fontFamily('mono')->searchable(),
                TextColumn::make('datetime')->label('Data/Hora')->sortable()->searchable(),
                TextColumn::make('provider')->label('Provedor')->searchable()->wrap(),
                TextColumn::make('city')->label('Cidade')->searchable(),
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('period')->label('Faixa')->badge()->default('23h–06h'),
            ])
            ->defaultSort('datetime', 'desc')
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
                $dt = mb_strtolower((string) data_get($row, 'datetime', ''));
                $provider = mb_strtolower((string) data_get($row, 'provider', ''));
                $city = mb_strtolower((string) data_get($row, 'city', ''));
                $type = mb_strtolower((string) data_get($row, 'type', ''));

                return str_contains($ip, $needle)
                    || str_contains($dt, $needle)
                    || str_contains($provider, $needle)
                    || str_contains($city, $needle)
                    || str_contains($type, $needle);
            }));
        }

        usort($filtered, fn ($a, $b) => strcmp((string) data_get($b, 'datetime', ''), (string) data_get($a, 'datetime', '')));

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
