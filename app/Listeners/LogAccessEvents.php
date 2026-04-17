<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LogAccessEvents
{
    public function __construct(private Request $request) {}

    public function handle(object $event): void
    {
        $ip = $this->request->ip();
        $ua = substr((string) $this->request->userAgent(), 0, 2000);
        $route = optional($this->request->route())->getName();

        if ($event instanceof Login) {
            AuditLog::create([
                'user_id' => $event->user?->id,
                'email' => $event->user?->email,
                'action' => 'login_success',
                'route' => $route,
                'method' => $this->request->method(),
                'url' => $this->request->fullUrl(),
                'ip' => $ip,
                'user_agent' => $ua,
                'meta' => ['guard' => $event->guard],
                'occurred_at' => now(),
            ]);
            return;
        }

        if ($event instanceof Logout) {
            AuditLog::create([
                'user_id' => $event->user?->id,
                'email' => $event->user?->email,
                'action' => 'logout',
                'route' => $route,
                'method' => $this->request->method(),
                'url' => $this->request->fullUrl(),
                'ip' => $ip,
                'user_agent' => $ua,
                'meta' => ['guard' => $event->guard],
                'occurred_at' => now(),
            ]);
            return;
        }

        if ($event instanceof Failed) {
            $creds = (array) $event->credentials;
            $email = $creds['email'] ?? $creds['username'] ?? null;

            AuditLog::create([
                'user_id' => $event->user?->id,
                'email' => is_string($email) ? $email : null,
                'action' => 'login_failed',
                'route' => $route,
                'method' => $this->request->method(),
                'url' => $this->request->fullUrl(),
                'ip' => $ip,
                'user_agent' => $ua,
                'meta' => ['guard' => $event->guard],
                'occurred_at' => now(),
            ]);
        }
    }
}
