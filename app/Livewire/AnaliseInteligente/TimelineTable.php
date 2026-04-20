<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class TimelineTable extends TableComponent
{
    public array $rows = [];

    public function table(Table $table): Table
    {
        return $table
            // Habilita o campo de pesquisa mesmo sem colunas searchable()
            ->searchable()

            // Filtra manualmente (LIKE padrão) pelo IP
            ->records(function (?string $search): Collection {
                $records = collect($this->rows);

                $search = trim((string) $search);

                if ($search !== '') {
                    $records = $records->filter(function ($row) use ($search) {
                        $ip = (string) data_get($row, 'ip', '');

                        // LIKE padrão: "contém"
                        return str_contains($ip, $search);
                    })->values();
                }

                return $records;
            })

            ->columns([
                TextColumn::make('datetime')->label('Data/Hora')->sortable(),
                TextColumn::make('ip')->label('IP')->fontFamily('mono'),
                TextColumn::make('provider')->label('Provedor'),
                TextColumn::make('city')->label('Cidade'),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
            ])

            ->defaultSort('datetime', 'desc')

            // ✅ Paginação: 100 por página como padrão
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(100);
    }
}
