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
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ContactsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    /**
     * Lista de telefones crus (ex: "5566999999999")
     */
    public array $contacts = [];

    public function mount(array $contacts = []): void
    {
        // garante array "flat" e reindexado
        $this->contacts = array_values(array_filter($contacts, fn ($v) => is_string($v) && trim($v) !== ''));
    }

    public function table(Table $table): Table
    {
        return $table
            // Filament v4: custom data, sem Eloquent
            ->records(fn (): array => $this->buildRows())
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->badge()
                    ->color('warning') // laranja
                    ->alignCenter(),

                TextColumn::make('formatted')
                    ->label('Número')
                    ->badge()
                    ->color('info') // azul
                    ->fontFamily('mono')
                    ->extraAttributes(['class' => 'select-text']),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    private function buildRows(): array
    {
        $rows = [];

        foreach ($this->contacts as $i => $raw) {
            $rows[] = [
                'index' => $i + 1,
                'raw' => $raw,
                'formatted' => $this->formatPhone($raw),
            ];
        }

        return $rows;
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
