<?php

namespace App\Livewire\AnaliseInteligente;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;

class ContactsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public array $contacts = [];

    public function mount(array $contacts = []): void
    {
        $this->contacts = array_values(array_filter($contacts, fn ($v) => is_string($v) && trim($v) !== ''));
    }

    public function table(Table $table): Table
    {
        return $table
            ->searchable()
            // ✅ aqui devolvemos um paginator (não array)
            ->records(fn (?string $search): LengthAwarePaginatorContract => $this->buildPaginator($search))
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                TextColumn::make('formatted')
                    ->label('Número')
                    ->badge()
                    ->color('info')
                    ->fontFamily('mono')
                    ->extraAttributes(['class' => 'select-text']),

                TextColumn::make('raw')
                    ->label('Raw')
                    ->fontFamily('mono')
                    ->extraAttributes(['class' => 'select-text'])
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    /**
     * ✅ Pagina e busca manualmente, devolvendo LengthAwarePaginator.
     * Assim o Filament renderiza só 25 itens por página de verdade.
     */
    private function buildPaginator(?string $search = null): LengthAwarePaginator
    {
        $search = trim((string) $search);

        // 1) filtra
        $filtered = [];
        $idx = 1;

        foreach ($this->contacts as $raw) {
            $formatted = $this->formatPhone($raw);

            if ($search !== '') {
                $needle = mb_strtolower($search);
                if (
                    ! str_contains(mb_strtolower($raw), $needle) &&
                    ! str_contains(mb_strtolower($formatted), $needle)
                ) {
                    continue;
                }
            }

            $filtered[] = [
                'index' => $idx++,
                'raw' => $raw,
                'formatted' => $formatted,
            ];
        }

        // 2) pagina
        $perPage = (int) ($this->getTableRecordsPerPage() ?: 25);
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

    private function formatPhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (! str_starts_with($digits, '55')) {
            return $raw;
        }

        $rest = substr($digits, 2);

        if (strlen($rest) === 10) {
            $ddd = substr($rest, 0, 2);
            $p1 = substr($rest, 2, 4);
            $p2 = substr($rest, 6, 4);
            return "+55 ({$ddd}) {$p1}-{$p2}";
        }

        if (strlen($rest) === 11) {
            $ddd = substr($rest, 0, 2);
            $p1 = substr($rest, 2, 5);
            $p2 = substr($rest, 7, 4);
            return "+55 ({$ddd}) {$p1}-{$p2}";
        }

        return '+' . $digits;
    }

    public function render(): View
    {
        return view('livewire.analise-inteligente.contacts-table');
    }
}
