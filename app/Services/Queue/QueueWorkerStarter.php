<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class QueueWorkerStarter
{
    private function resolveCliPhpBinary(): string
    {
        $phpBinary = PHP_BINARY;
        $phpDir = dirname($phpBinary);
        $phpBase = basename($phpBinary);

        $candidates = array_values(array_unique(array_filter([
            str_contains($phpBase, 'fpm')
                ? $phpDir . DIRECTORY_SEPARATOR . str_replace('-fpm', '', $phpBase)
                : $phpBinary,
            $phpDir . DIRECTORY_SEPARATOR . 'php',
            $phpBinary,
            'php',
        ], fn ($candidate) => is_string($candidate) && $candidate !== '')));

        foreach ($candidates as $candidate) {
            if ($candidate === 'php') {
                return $candidate;
            }

            if (is_file($candidate) && is_executable($candidate) && ! str_contains(basename($candidate), 'fpm')) {
                return $candidate;
            }
        }

        return 'php';
    }

    public function start(): void
    {
        $phpBinary = $this->resolveCliPhpBinary();

        $command = sprintf(
            '%s artisan queue:work %s --stop-when-empty --queue=default --tries=3 --timeout=300 >> %s 2>&1 &',
            escapeshellarg($phpBinary),
            escapeshellarg((string) config('queue.default', 'database')),
            escapeshellarg(storage_path('logs/queue-worker.log')),
        );

        Log::channel('agenda_mail')->warning('Worker residente ausente; iniciando worker temporario.', [
            'command' => $command,
            'php_binary_web' => PHP_BINARY,
            'php_binary_queue' => $phpBinary,
        ]);

        Process::path(base_path())
            ->quietly()
            ->run(['/bin/sh', '-lc', $command]);
    }
}
