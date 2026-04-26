<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Cache;

class QueueHealthService
{
    private const HEARTBEAT_KEY = 'queue:worker:heartbeat';
    private const HEARTBEAT_TTL_SECONDS = 30;

    public function touchHeartbeat(): void
    {
        Cache::store('file')->put(
            self::HEARTBEAT_KEY,
            now()->toIso8601String(),
            now()->addSeconds(self::HEARTBEAT_TTL_SECONDS * 2),
        );
    }

    public function isWorkerAlive(): bool
    {
        $lastHeartbeat = Cache::store('file')->get(self::HEARTBEAT_KEY);

        if (! is_string($lastHeartbeat) || trim($lastHeartbeat) === '') {
            return false;
        }

        return now()->diffInSeconds($lastHeartbeat, absolute: true) <= self::HEARTBEAT_TTL_SECONDS;
    }
}
