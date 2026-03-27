<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RelatoriosProcessados extends Page
{
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

    public string $statusFilter = 'done'; // done|running|error|all

    public function getRunsProperty()
    {
        $q = AnaliseRun::query()->orderByDesc('id');

        if ($this->statusFilter !== 'all') {
            $q->where('status', $this->statusFilter);
        }

        return $q->limit(100)->get();
    }

    public function deleteRun(int $runId): void
    {
        $run = AnaliseRun::find($runId);

        if (! $run) {
            Notification::make()->title('Run não encontrado')->danger()->send();
            return;
        }

        // ✅ Apaga o run + (cascade) analise_run_ips
        $run->delete();

        Notification::make()->title('Relatório excluído')->success()->send();
    }
}
