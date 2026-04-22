<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    protected static array $auditHiddenFields = [
        'password',
        'remember_token',
        'current_password',
        'new_password',
        'password_confirmation',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            self::write('created', $model, null, self::sanitizeAuditValues($model->getAttributes()));
        });

        static::updated(function (Model $model) {
            $dirty = $model->getChanges();
            unset($dirty['updated_at']);

            if ($dirty === []) {
                return;
            }

            $old = array_intersect_key($model->getOriginal(), $dirty);
            $new = array_intersect_key($model->getAttributes(), $dirty);

            self::write('updated', $model, self::sanitizeAuditValues($old), self::sanitizeAuditValues($new));
        });

        static::deleted(function (Model $model) {
            self::write('deleted', $model, self::sanitizeAuditValues($model->getOriginal()), null);
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

            'meta' => array_filter([
                'filament' => self::resolveFilamentContextFromRequest(),
                'model_label' => self::resolveAuditModelLabel($model),
                'record_label' => self::resolveAuditRecordLabel($model),
                'changed_fields' => is_array($new) ? array_keys($new) : null,
            ], fn ($v) => $v !== null),

            'occurred_at' => now(),
        ]);
    }

    private static function sanitizeAuditValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sanitized = [];

        foreach ($values as $key => $value) {
            if (in_array((string) $key, static::$auditHiddenFields, true)) {
                $sanitized[$key] = '[oculto]';
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $sanitized[$key] = $value->format('Y-m-d H:i:s');
                continue;
            }

            if ($value instanceof ArrayObject) {
                $value = $value->getArrayCopy();
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeAuditValues($value);
                continue;
            }

            if (is_object($value)) {
                $sanitized[$key] = method_exists($value, '__toString') ? (string) $value : get_debug_type($value);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private static function resolveAuditModelLabel(Model $model): string
    {
        $class = class_basename($model::class);

        return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $class) ?? $class);
    }

    private static function resolveAuditRecordLabel(Model $model): ?string
    {
        foreach (['name', 'nome', 'title', 'titulo', 'email', 'target', 'uuid'] as $field) {
            $value = $model->getAttribute($field);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
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
