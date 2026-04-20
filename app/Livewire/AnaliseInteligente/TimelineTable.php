<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TimelineTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            // ✅ habilita busca (campo de pesquisa)
            ->searchable()

            // ✅ agora devolvemos um paginator, não Collection inteira
            ->records(fn (?string $search): LengthAwarePaginatorContract => $this->buildPaginator($search))

            ->columns([
                TextColumn::make('datetime')->label('Data/Hora')->sortable(),

                TextColumn::make('ip')
                    ->label('IP')
                    ->fontFamily('mono')
                    ->extraAttributes(['class' => 'select-text'])
                    ->wrap(),

                TextColumn::make('provider')->label('Provedor')->wrap(),
                TextColumn::make('city')->label('Cidade')->wrap(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
            ])

            ->defaultSort('datetime', 'desc')

            // ✅ paginação real (default 100)
            ->paginated([25, 50, 100, 200])
            ->defaultPaginationPageOption(100);
    }

    private function buildPaginator(?string $search = null): LengthAwarePaginator
    {
        $search = trim((string) $search);

        // 1) filtra em memória
        $filtered = $this->rows;

        if ($search !== '') {
            $needle = mb_strtolower($search);

            $filtered = array_values(array_filter($filtered, function ($row) use ($needle) {
                $ip = mb_strtolower((string) data_get($row, 'ip', ''));
                $provider = mb_strtolower((string) data_get($row, 'provider', ''));
                $city = mb_strtolower((string) data_get($row, 'city', ''));
                $type = mb_strtolower((string) data_get($row, 'type', ''));
                $dt = mb_strtolower((string) data_get($row, 'datetime', ''));

                // busca simples: contém
                return str_contains($ip, $needle)
                    || str_contains($provider, $needle)
                    || str_contains($city, $needle)
                    || str_contains($type, $needle)
                    || str_contains($dt, $needle);
            }));
        }

        // 2) respeita sort padrão (datetime desc) quando não houver sort do usuário
        // Como TableComponent nem sempre aplica sort em arrays, garantimos aqui:
        usort($filtered, fn ($a, $b) => strcmp((string) data_get($b, 'datetime', ''), (string) data_get($a, 'datetime', '')));

        // 3) paginação (pega estado do Filament Table)
        $perPage = (int) ($this->getTableRecordsPerPage() ?: 100);
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
