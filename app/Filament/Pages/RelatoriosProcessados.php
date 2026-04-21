<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
        return 'Análise Telemática';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->query($this->getTableQuery())
            ->defaultSort('id', 'desc')

            ->toolbarActions([
                BulkAction::make('deleteSelected')
                    ->label('Excluir selecionados')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Excluir relatórios selecionados')
                    ->modalDescription('Essa ação não pode ser desfeita. Deseja realmente excluir os relatórios selecionados?')
                    ->modalSubmitActionLabel('Excluir')
                    ->fetchSelectedRecords(false)
                    ->action(function (Collection $records): void {
                        $ids = $records->values()->all();
                        $count = count($ids);

                        if ($count === 0) {
                            Notification::make()
                                ->title('Nenhum relatório selecionado.')
                                ->warning()
                                ->send();
                            return;
                        }

                        AnaliseRun::query()->whereKey($ids)->delete();

                        Notification::make()
                            ->title("{$count} relatório(s) excluído(s) com sucesso.")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])

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

                Tables\Columns\TextColumn::make('target')
                    ->label('Alvo')
                    ->state(function (AnaliseRun $record): string {
                        $source = $this->resolveSource($record);

                        if ($source === 'instagram') {
                            return $this->resolveInstagramAlvo($record);
                        }

                        if ($source === 'generico') {
                            return 'Run #' . $record->id;
                        }

                        return $record->target ?: '-';
                    })
                    ->searchable()
                    ->copyable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Criado por')
                    ->state(fn (AnaliseRun $record) => $record->user?->name ?? '—')
                    ->sortable()
                    ->searchable(),

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

                        if ($value === 'generico') {
                            return $query->where(function (Builder $q) {
                                $q->where('report->_source', 'generico')
                                    ->orWhere('report->_source', 'generic');
                            });
                        }

                        return $query->where('report->_source', $value);
                    }),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    protected function getTableQuery(): Builder
    {
        return AnaliseRun::query()
            ->with('user')
            ->select([
                'analise_runs.id',
                'analise_runs.user_id',
                'analise_runs.target',
                'analise_runs.report', // ✅ precisa para pegar account_identifier / first_name do _parsed
                'analise_runs.status',
                'analise_runs.progress',
                'analise_runs.total_unique_ips',
                'analise_runs.processed_unique_ips',
                'analise_runs.created_at',
            ])
            ->selectRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(analise_runs.report, '$._source'))) as source_extracted");
    }

    protected function resolveInstagramAlvo(AnaliseRun $run): string
    {
        $report = $run->report;

        if (is_string($report) && trim($report) !== '') {
            $decoded = json_decode($report, true);
            if (is_array($decoded)) {
                $report = $decoded;
            }
        }

        $handle = trim((string) data_get($report, '_parsed.account_identifier'));
        if ($handle !== '' && ! preg_match('/^\d+$/', $handle)) {
            return str_starts_with($handle, '@') ? $handle : "@{$handle}";
        }

        $name = trim((string) data_get($report, '_parsed.first_name'));
        if ($name !== '') {
            return $name;
        }

        $fallback = trim((string) ($run->target ?? ''));
        return $fallback !== '' ? $fallback : '—';
    }

    public function resolveSource(AnaliseRun $run): string
    {
        $source = $run->source_extracted ?? null;

        if (is_string($source) && trim($source) !== '') {
            $source = strtolower(trim($source));

            if ($source === 'generic') {
                return 'generico';
            }

            if (in_array($source, ['whatsapp', 'instagram', 'generico'], true)) {
                return $source;
            }
        }

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
        $run = AnaliseRun::query()->find($runId);

        if (! $run) {
            Notification::make()->title('Relatório não encontrado')->danger()->send();
            return;
        }

        $run->delete();

        Notification::make()->title('Relatório excluído')->success()->send();
    }
}
