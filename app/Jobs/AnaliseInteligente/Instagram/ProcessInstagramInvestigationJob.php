<?php

namespace App\Jobs\AnaliseInteligente\Instagram;

use App\Actions\AnaliseInteligente\Instagram\CreateInstagramRunsAction;
use App\Models\AnaliseInvestigation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessInstagramInvestigationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $investigationId,
        public int $userId,
        public array $storedPaths,
        public string $batchId,
    ) {}

    public function handle(CreateInstagramRunsAction $action): void
    {
        $investigation = AnaliseInvestigation::find($this->investigationId);
        if (! $investigation) {
            return;
        }

        $action->execute($investigation, $this->userId, $this->storedPaths, $this->batchId);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Falha ao processar investigacao do Instagram.', [
            'investigation_id' => $this->investigationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
