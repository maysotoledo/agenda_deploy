<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::write('created', $model, null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $dirty = $model->getChanges();

            $old = array_intersect_key($model->getOriginal(), $dirty);
            $new = array_intersect_key($model->getAttributes(), $dirty);

            self::write('updated', $model, $old, $new);
        });

        static::deleted(function (Model $model) {
            self::write('deleted', $model, $model->getOriginal(), null);
        });
    }

    protected static function write(string $action, Model $model, ?array $old, ?array $new): void
    {
        $user = Auth::user();
        $req = request();

        AuditLog::create([
            'user_id' => $user?->id,
            'email' => $user?->email,

            'action' => $action,
            'model_type' => $model::class,
            'model_id' => (string) $model->getKey(),

            'route' => optional($req->route())->getName(),
            'method' => $req->method(),
            'url' => $req->fullUrl(),
            'ip' => $req->ip(),
            'user_agent' => substr((string) $req->userAgent(), 0, 2000),

            'old_values' => $old,
            'new_values' => $new,

            // ✅ origem (filament panel/resource/page) + campos alterados
            'meta' => array_filter([
                'filament' => self::resolveFilamentContextFromRequest(),
                'changed_fields' => is_array($new) ? array_keys($new) : null,
            ], fn ($v) => $v !== null),

            'occurred_at' => now(),
        ]);
    }

    private static function resolveFilamentContextFromRequest(): array
    {
        $req = request();
        $routeName = (string) optional($req->route())->getName();

        $panel = null;
        $resourceSlug = null;
        $pageKey = null;

        if (preg_match('/^filament\.(?<panel>[^.]+)\.(?<rest>.+)$/', $routeName, $m)) {
            $panel = $m['panel'];
            $rest = $m['rest'];

            if (preg_match('/^resources\.(?<resource>[^.]+)\.(?<page>.+)$/', $rest, $mm)) {
                $resourceSlug = $mm['resource'];
                $pageKey = $mm['page'];
            } elseif (preg_match('/^pages\.(?<page>.+)$/', $rest, $mm)) {
                $pageKey = $mm['page'];
            }
        }

        return array_filter([
            'panel' => $panel,
            'resource_slug' => $resourceSlug,
            'page' => $pageKey,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
