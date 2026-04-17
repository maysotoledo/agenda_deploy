<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogRequests
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // ✅ só painel
        if (! $request->is('admin*')) {
            return $response;
        }

        // ✅ ignora ruído (polling/livewire/assets)
        if (
            $request->is('admin/livewire*') ||
            $request->is('admin/notifications*') ||
            $request->is('admin/assets*') ||
            $request->is('admin/js*') ||
            $request->is('admin/css*') ||
            $request->is('livewire*') ||
            $request->is('storage*')
        ) {
            return $response;
        }

        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'email' => $user?->email,
            'action' => 'request',
            'route' => optional($request->route())->getName(),
            'method' => strtoupper($request->method()),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 2000),
            'meta' => array_merge([
                'status' => $response->getStatusCode(),
            ], $this->resolveFilamentContext($request)),
            'occurred_at' => now(),
        ]);

        return $response;
    }

    /**
     * Captura o “onde” (Filament panel/resource/page) usando o route name.
     * Exemplos comuns:
     * - filament.admin.pages.dashboard
     * - filament.admin.resources.eventos.index
     * - filament.admin.resources.eventos.create
     * - filament.admin.resources.eventos.edit
     */
    private function resolveFilamentContext(Request $request): array
    {
        $routeName = (string) optional($request->route())->getName();

        $panel = null;
        $resourceSlug = null;
        $pageKey = null;

        if (preg_match('/^filament\.(?<panel>[^.]+)\.(?<rest>.+)$/', $routeName, $m)) {
            $panel = $m['panel'];
            $rest = $m['rest'];

            if (preg_match('/^resources\.(?<resource>[^.]+)\.(?<page>.+)$/', $rest, $mm)) {
                $resourceSlug = $mm['resource']; // ex: "eventos"
                $pageKey = $mm['page'];          // ex: "create", "index"
            } elseif (preg_match('/^pages\.(?<page>.+)$/', $rest, $mm)) {
                $pageKey = $mm['page'];          // ex: "dashboard"
            }
        }

        // salva dentro de meta.filament.*
        return array_filter([
            'filament' => array_filter([
                'panel' => $panel,
                'resource_slug' => $resourceSlug,
                'page' => $pageKey,
            ], fn ($v) => $v !== null && $v !== ''),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
