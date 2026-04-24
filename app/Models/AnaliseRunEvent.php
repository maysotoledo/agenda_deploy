<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnaliseRunEvent extends Model
{
    protected $fillable = [
        'analise_run_id',
        'event_type',
        'category',
        'occurred_at',
        'timezone_label',
        'ip',
        'logical_port',
        'action',
        'description',
        'title',
        'origin',
        'target',
        'url',
        'user_agent',
        'device_identifier_type',
        'device_identifier_value',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'datetime_local',
        'datetime_gmt',
        'provider_label',
        'city_label',
        'connection_type',
        'period_flags',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AnaliseRun::class, 'analise_run_id');
    }

    public function ipEnrichment(): BelongsTo
    {
        return $this->belongsTo(IpEnrichment::class, 'ip', 'ip');
    }

    protected function datetimeLocal(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->formatDate($this->occurred_at, 'America/Sao_Paulo'));
    }

    protected function datetimeGmt(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->occurred_at instanceof CarbonInterface) {
                return null;
            }

            $tzLabel = $this->timezone_label ?: 'UTC';

            return $this->occurred_at->copy()->timezone('UTC')->format('d/m/Y H:i:s') . " ({$tzLabel})";
        });
    }

    protected function providerLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $provider = trim((string) ($this->ipEnrichment?->isp ?: $this->ipEnrichment?->org));

            return $provider !== '' ? $provider : 'Desconhecido';
        });
    }

    protected function cityLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $city = trim((string) ($this->ipEnrichment?->city ?? ''));

            return $city !== '' ? $city : 'Desconhecida';
        });
    }

    protected function connectionType(): Attribute
    {
        return Attribute::get(fn (): string => ($this->ipEnrichment?->mobile ?? false) ? 'Movel' : 'Residencial');
    }

    protected function periodFlags(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->occurred_at instanceof CarbonInterface) {
                return 'Regular';
            }

            $local = $this->occurred_at->copy()->timezone('America/Sao_Paulo');
            $hour = (int) $local->format('G');
            $isNight = $hour >= 23 || $hour <= 6;
            $isWeekend = (int) $local->format('N') >= 6;

            return implode(', ', array_filter([
                $isNight ? 'Noturno' : null,
                $isWeekend ? 'Fim de semana' : null,
            ])) ?: 'Regular';
        });
    }

    private function formatDate(mixed $value, string $timezone): ?string
    {
        if (! $value instanceof CarbonInterface) {
            return null;
        }

        return $value->copy()->timezone($timezone)->format('d/m/Y H:i:s');
    }
}
