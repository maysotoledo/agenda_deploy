<?php

namespace App\Listeners;

use App\Models\AccessLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class LogAccessEvents
{
    public function __construct(private Request $request) {}

    public function handle(object $event): void
    {
        $ip = $this->request->ip();
        $ua = substr((string) $this->request->userAgent(), 0, 2000);

        if ($event instanceof Login) {
            AccessLog::create([
                'user_id' => $event->user?->id,
                'email' => $event->user?->email,
                'event' => 'login_success',
                'ip' => $ip,
                'user_agent' => $ua,
                'occurred_at' => now(),
                'meta' => ['guard' => $event->guard],
            ]);
            return;
        }

        if ($event instanceof Failed) {
            $creds = (array) $event->credentials;
            $email = $creds['email'] ?? $creds['username'] ?? null;

            AccessLog::create([
                'user_id' => $event->user?->id,
                'email' => is_string($email) ? $email : null,
                'event' => 'login_failed',
                'ip' => $ip,
                'user_agent' => $ua,
                'occurred_at' => now(),
                'meta' => ['guard' => $event->guard],
            ]);
        }
    }
}
