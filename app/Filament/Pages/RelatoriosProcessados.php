<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class RelatoriosProcessados extends Page implements HasTable
{
    use HasPageShield;
    use Tables\Concerns\InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Relatórios Processados';
    protected static ?string $title = 'Relatórios Processados';
    protected static ?string $slug = 'relatorios-processados';

    protected string $view = 'filament.pages.relatorios-processados';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Investigação Telemática';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('source_label')
                    ->label('Tipo')
                    ->state(fn (AnaliseRun $record): string => $this->resolveSourceLabel($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'WhatsApp' => 'success',
                        'Instagram' => 'info',
                        'Genérico' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('target_display')
                    ->label('Alvo')
                    ->state(function (AnaliseRun $record): string {
                        $source = $this->resolveSource($record);

                        // 📸 Instagram → mostra o @usuario
                        if ($source === 'instagram') {
                            return data_get($record->report, '_parsed.account_identifier') ?? '-';
                        }

                        // 🧾 Genérico → mostra nome do arquivo (quando existir), senão ID
                        if ($source === 'generico') {
                            $file = data_get($record->report, '_file');
                            return $file ? basename((string) $file) : ('Run #' . $record->id);
                        }

                        // 📱 WhatsApp → mostra o telefone
                        return $record->target ?? '-';
                    })
                    ->searchable()
                    ->copyable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'done' => 'Concluído',
                        'running' => 'Processando',
                        'error' => 'Erro',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'done' => 'success',
                        'running' => 'warning',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Progresso')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_unique_ips')
                    ->label('IPs únicos')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_unique_ips')
                    ->label('IPs processados')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                Tables\Columns\ViewColumn::make('acoes')
                    ->label('Ações')
                    ->view('filament.pages.partials.relatorios-processados-acoes'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'done' => 'Finalizado',
                        'running' => 'Processando',
                        'error' => 'Erro',
                    ]),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Tipo')
                    ->options([
                        'whatsapp' => 'WhatsApp',
                        'instagram' => 'Instagram',
                        'generico' => 'Genérico',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        // aceita também runs antigos salvos como "generic"
                        if ($value === 'generico') {
                            return $query->where(function (Builder $q) {
                                $q->where('report->_source', 'generico')
                                  ->orWhere('report->_source', 'generic');
                            });
                        }

                        return $query->where('report->_source', $value);
                    }),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    protected function getTableQuery(): Builder
    {
        return AnaliseRun::query()->orderByDesc('id');
    }

    /**
     * ✅ Agora reconhece "generico" (e "generic" legado).
     * ✅ Se não vier _source, tenta inferir por estrutura do _parsed.
     */
    public function resolveSource(AnaliseRun $run): string
    {
        $source = data_get($run->report, '_source');

        if (is_string($source) && trim($source) !== '') {
            $source = strtolower(trim($source));

            if ($source === 'generic') {
                return 'generico';
            }

            if (in_array($source, ['whatsapp', 'instagram', 'generico'], true)) {
                return $source;
            }
        }

        // fallback para runs antigos sem _source salvo:
        // Instagram costuma ter esses campos
        if (
            data_get($run->report, '_parsed.first_name') !== null ||
            data_get($run->report, '_parsed.account_identifier') !== null ||
            data_get($run->report, '_parsed.registration_ip') !== null
        ) {
            return 'instagram';
        }

        // Genérico: nosso parser salva "events" (lista de eventos com ip/time)
        if (is_array(data_get($run->report, '_parsed.events'))) {
            return 'generico';
        }

        // padrão histórico
        return 'whatsapp';
    }

    public function resolveSourceLabel(AnaliseRun $run): string
    {
        return match ($this->resolveSource($run)) {
            'instagram' => 'Instagram',
            'whatsapp' => 'WhatsApp',
            'generico' => 'Genérico',
            default => 'Genérico',
        };
    }

    /**
     * ✅ Agora abre no Genérico quando for generico
     */
    public function resolveViewUrl(AnaliseRun $run): string
    {
        return match ($this->resolveSource($run)) {
            'instagram' => AnaliseInteligenteInsta::getUrl(['run' => $run->id]),
            'generico' => AnaliseInteligenteGenerico::getUrl(['run' => $run->id]),
            default => AnaliseInteligenteWPP::getUrl(['run' => $run->id]),
        };
    }

    #[On('delete-run')]
    public function deleteRun(int $runId): void
    {
        $run = AnaliseRun::find($runId);

        if (! $run) {
            Notification::make()->title('Relatório não encontrado')->danger()->send();
            return;
        }

        $run->delete();

        Notification::make()->title('Relatório excluído')->success()->send();
    }
}
