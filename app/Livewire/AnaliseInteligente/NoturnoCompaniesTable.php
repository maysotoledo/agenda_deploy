<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Support\Collection;

class NoturnoCompaniesTable extends TableComponent
{
    public array $rows = [];

    public function openCompany(string $company): void
    {
        $this->dispatch('open-night-company', company: $company);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->rows))
            ->columns([
                TextColumn::make('company')
                    ->label('Empresa / Provedor')
                    ->searchable()
                    ->sortable()
                    ->action(fn ($record) => $this->openCompany($record['company'])),

                TextColumn::make('unique_ips')
                    ->label('IPs únicos (noturno)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('occurrences')
                    ->label('Ocorrências (noturno)')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('unique_ips', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
