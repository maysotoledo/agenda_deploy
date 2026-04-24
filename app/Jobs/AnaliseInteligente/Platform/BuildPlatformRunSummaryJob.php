<?php

namespace App\Jobs\AnaliseInteligente\Platform;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunStep;
use App\Services\AnaliseInteligente\Platform\PlatformRunSummaryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class BuildPlatformRunSummaryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $runId) {}

    public function handle(PlatformRunSummaryService $summaryService): void
    {
        $run = AnaliseRun::find($this->runId);
        if (! $run) {
            return;
        }

        AnaliseRunStep::updateOrCreate(
            ['analise_run_id' => $run->id, 'step' => 'build_summary'],
            ['status' => 'running', 'total' => 1, 'processed' => 0, 'started_at' => now(), 'message' => 'Consolidando resumo da analise.'],
        );

        $summaryService->buildSummary($run);

        AnaliseRunStep::updateOrCreate(
            ['analise_run_id' => $run->id, 'step' => 'build_summary'],
            ['status' => 'done', 'total' => 1, 'processed' => 1, 'finished_at' => now(), 'message' => 'Resumo consolidado.'],
        );
    }
}
