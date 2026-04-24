<?php

namespace App\Actions\AnaliseInteligente\Platform;

use App\Models\AnaliseInvestigation;
use App\Models\AnaliseRun;
use App\Models\AnaliseRunContact;
use App\Models\AnaliseRunEvent;
use App\Models\AnaliseRunIp;
use App\Models\AnaliseRunStep;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PersistPlatformRunAction
{
    public function execute(
        AnaliseInvestigation $investigation,
        int $userId,
        string $source,
        string $label,
        string $batchId,
        array $group,
        array $ipsMap,
    ): AnaliseRun {
        $parsed = (array) ($group['parsed'] ?? []);
        $target = trim((string) ($group['target'] ?? '')) ?: trim((string) ($parsed['target'] ?? '')) ?: null;

        return DB::transaction(function () use ($investigation, $userId, $source, $label, $batchId, $group, $ipsMap, $parsed, $target) {
            $run = AnaliseRun::create([
                'user_id' => $userId,
                'investigation_id' => $investigation->id,
                'uuid' => (string) str()->uuid(),
                'source' => $source,
                'target' => $target,
                'total_unique_ips' => count($ipsMap),
                'processed_unique_ips' => 0,
                'progress' => count($ipsMap) === 0 ? 70 : 10,
                'status' => 'queued',
                'started_at' => now(),
                'summary' => [
                    '_source' => $source,
                    '_platform_label' => $label,
                    '_batch_id' => $batchId,
                    '_files' => array_values((array) ($group['files'] ?? [])),
                    '_fragments' => array_values((array) ($group['fragments'] ?? [])),
                    'accounts_found' => array_values((array) ($parsed['emails'] ?? [])),
                    'phones_found' => array_values((array) ($parsed['phones'] ?? [])),
                    'identifiers_found' => array_values((array) ($parsed['identifiers'] ?? [])),
                    'subscriber_info' => $parsed['google_subscriber_info'] ?? null,
                ],
            ]);

            $this->persistContacts($run, $parsed);
            $this->persistAccessEvents($run, $parsed);
            $this->persistMapsAndSearch($run, $parsed);

            foreach ($ipsMap as $ip => $meta) {
                AnaliseRunIp::updateOrCreate(
                    ['analise_run_id' => $run->id, 'ip' => $ip],
                    [
                        'occurrences' => (int) ($meta['occurrences'] ?? 0),
                        'last_seen_at' => ! empty($meta['last_seen_ts'])
                            ? now()->setTimestamp((int) $meta['last_seen_ts'])
                            : null,
                        'enriched' => false,
                    ],
                );
            }

            foreach ([
                ['step' => 'parse', 'status' => 'done', 'total' => 1, 'processed' => 1, 'started_at' => now(), 'finished_at' => now(), 'message' => 'Arquivos parseados e persistidos.'],
                ['step' => 'enrich_ips', 'status' => count($ipsMap) > 0 ? 'queued' : 'done', 'total' => count($ipsMap), 'processed' => 0, 'started_at' => null, 'finished_at' => count($ipsMap) > 0 ? null : now(), 'message' => count($ipsMap) > 0 ? 'Aguardando enriquecimento dos IPs.' : 'Sem IPs publicos para enriquecer.'],
                ['step' => 'build_summary', 'status' => 'queued', 'total' => 1, 'processed' => 0, 'started_at' => null, 'finished_at' => null, 'message' => 'Resumo ainda nao consolidado.'],
            ] as $stepData) {
                AnaliseRunStep::updateOrCreate(
                    ['analise_run_id' => $run->id, 'step' => $stepData['step']],
                    $stepData,
                );
            }

            return $run;
        });
    }

    private function persistContacts(AnaliseRun $run, array $parsed): void
    {
        foreach ((array) ($parsed['emails'] ?? []) as $email) {
            $email = trim((string) $email);
            if ($email === '') {
                continue;
            }

            AnaliseRunContact::updateOrCreate(
                ['analise_run_id' => $run->id, 'phone' => md5('email:' . mb_strtolower($email))],
                ['contact_type' => 'email', 'value' => $email, 'name' => $email],
            );
        }

        foreach ((array) ($parsed['phones'] ?? []) as $phone) {
            $phone = trim((string) $phone);
            if ($phone === '') {
                continue;
            }

            AnaliseRunContact::updateOrCreate(
                ['analise_run_id' => $run->id, 'phone' => $phone],
                ['contact_type' => 'phone', 'value' => $phone, 'name' => $phone],
            );
        }

        foreach ((array) ($parsed['identifiers'] ?? []) as $identifier) {
            $type = trim((string) ($identifier['type'] ?? 'ID'));
            $value = trim((string) ($identifier['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            AnaliseRunContact::updateOrCreate(
                ['analise_run_id' => $run->id, 'phone' => md5("identifier:{$type}:{$value}")],
                ['contact_type' => 'identifier', 'value' => $value, 'name' => $type, 'metadata' => ['type' => $type]],
            );
        }
    }

    private function persistAccessEvents(AnaliseRun $run, array $parsed): void
    {
        foreach ((array) ($parsed['events'] ?? []) as $event) {
            $occurredAt = $this->normalizeDate($event['time_utc'] ?? null);

            AnaliseRunEvent::create([
                'analise_run_id' => $run->id,
                'event_type' => 'access',
                'occurred_at' => $occurredAt,
                'timezone_label' => $event['tz_label'] ?? 'UTC',
                'ip' => $event['ip'] ?? null,
                'logical_port' => $event['logical_port'] ?? null,
                'action' => $event['action'] ?? null,
                'description' => $event['description'] ?? null,
                'user_agent' => $event['user_agent'] ?? null,
                'device_identifier_type' => ! empty($event['android_id']) ? 'Android ID' : (! empty($event['ios_idfv']) ? 'Apple iOS IDFV' : null),
                'device_identifier_value' => $event['android_id'] ?? $event['ios_idfv'] ?? null,
                'metadata' => array_filter([
                    'ip_version' => filter_var((string) ($event['ip'] ?? ''), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPv6' : 'IPv4',
                ]),
            ]);
        }
    }

    private function persistMapsAndSearch(AnaliseRun $run, array $parsed): void
    {
        foreach ((array) ($parsed['maps_rows'] ?? []) as $row) {
            AnaliseRunEvent::create([
                'analise_run_id' => $run->id,
                'event_type' => 'map',
                'category' => $row['type'] ?? null,
                'occurred_at' => $this->timestampToDate($row['datetime_ts'] ?? null),
                'description' => $row['summary'] ?? null,
                'title' => $row['type'] ?? null,
                'origin' => $row['origin'] ?? null,
                'target' => $row['target'] ?? null,
                'url' => $row['maps_url'] ?? null,
                'metadata' => $row,
            ]);
        }

        foreach ((array) ($parsed['search_rows'] ?? []) as $row) {
            AnaliseRunEvent::create([
                'analise_run_id' => $run->id,
                'event_type' => 'search',
                'occurred_at' => $this->timestampToDate($row['datetime_ts'] ?? null),
                'description' => $row['query'] ?? null,
                'target' => $row['query'] ?? null,
                'metadata' => $row,
            ]);
        }
    }

    private function normalizeDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->timezone('UTC');
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value, 'UTC')->timezone('UTC');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function timestampToDate(mixed $value): ?Carbon
    {
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value, 'UTC')->timezone('UTC');
        }

        return $this->normalizeDate($value);
    }
}
