<?php

namespace App\Services\AnaliseInteligente\Generic;

use Carbon\Carbon;

class GenericReportAggregator
{
    public function buildReport(array $parsed, array $enrichedByIp): array
    {
        $tz = 'America/Sao_Paulo';

        $events = $parsed['events'] ?? [];
        $emails = $parsed['emails'] ?? [];

        $timelineRows = [];
        $uniqueIpAgg = [];
        $providerAgg = [];
        $cityAgg = [];

        $nightTotalEvents = 0;
        $nightRows = [];

        $mobileTotalEvents = 0;
        $mobileRows = [];

        foreach ($events as $e) {
            $ip = $e['ip'] ?? null;
            $timeUtc = $this->toCarbonUtc($e['time_utc'] ?? null);

            if (! $ip || ! $timeUtc) {
                continue;
            }

            $info = $enrichedByIp[$ip] ?? ['city' => null, 'isp' => null, 'org' => null, 'mobile' => null];

            $providerRaw = trim(($info['isp'] ?? '') ?: ($info['org'] ?? ''));
            $cityRaw = trim((string) ($info['city'] ?? ''));
            $mobile = (bool) ($info['mobile'] ?? false);

            $provider = $providerRaw !== '' ? $providerRaw : 'Desconhecido';
            $city = $cityRaw !== '' ? $cityRaw : 'Desconhecida';
            $type = $mobile ? 'Móvel' : 'Residencial';

            $tzLabel = $e['tz_label'] ?? 'UTC';
            $logicalPort = $e['logical_port'] ?? null;

            $timeLocal = $timeUtc->copy()->setTimezone($tz);

            $row = [
                'datetime_gmt' => $timeUtc->format('Y-m-d H:i:s') . " ({$tzLabel})",
                'datetime_local' => $timeLocal->format('Y-m-d H:i:s') . ' (GMT-3)',
                'ip' => $ip,
                'provider' => $provider,
                'city' => $city,
                'connection_type' => $type,
                'logical_port' => $logicalPort,
                'action' => $e['action'] ?? null,
                'description' => $e['description'] ?? null,
            ];

            $timelineRows[] = $row;

            // ✅ NÃO APARECER "Desconhecido": só agrega se tiver provider real
            if ($providerRaw !== '') {
                $uniqueIpAgg[$ip] ??= [
                    'ip' => $ip,
                    'provider' => $provider,
                    'city' => $city,
                    'connection_type' => $type,
                    'count' => 0,
                    'last_seen' => $timeUtc,
                ];
                $uniqueIpAgg[$ip]['count']++;
                if ($timeUtc->greaterThan($uniqueIpAgg[$ip]['last_seen'])) {
                    $uniqueIpAgg[$ip]['last_seen'] = $timeUtc;
                }

                $providerAgg[$provider] ??= [
                    'provider' => $provider,
                    'occurrences' => 0,
                    'unique_ips' => [],
                    'cities' => [],
                    'mobile_occurrences' => 0,
                    'last_seen' => $timeUtc,
                ];
                $providerAgg[$provider]['occurrences']++;
                $providerAgg[$provider]['unique_ips'][$ip] = true;
                $providerAgg[$provider]['cities'][$city] = true;
                if ($mobile) $providerAgg[$provider]['mobile_occurrences']++;
                if ($timeUtc->greaterThan($providerAgg[$provider]['last_seen'])) {
                    $providerAgg[$provider]['last_seen'] = $timeUtc;
                }

                $cityAgg[$city] ??= [
                    'city' => $city,
                    'occurrences' => 0,
                    'unique_ips' => [],
                    'providers' => [],
                    'mobile_occurrences' => 0,
                    'last_seen' => $timeUtc,
                ];
                $cityAgg[$city]['occurrences']++;
                $cityAgg[$city]['unique_ips'][$ip] = true;
                $cityAgg[$city]['providers'][$provider] = true;
                if ($mobile) $cityAgg[$city]['mobile_occurrences']++;
                if ($timeUtc->greaterThan($cityAgg[$city]['last_seen'])) {
                    $cityAgg[$city]['last_seen'] = $timeUtc;
                }
            }

            $hour = (int) $timeLocal->format('G');
            $isNight = ($hour >= 23 || $hour <= 6);

            if ($isNight) {
                $nightTotalEvents++;
                $nightRows[] = $row;
            }

            if ($mobile) {
                $mobileTotalEvents++;
                $mobileRows[] = $row;
            }
        }

        usort($timelineRows, fn ($a, $b) => strcmp($b['datetime_local'], $a['datetime_local']));

        $uniqueIpRows = array_values($uniqueIpAgg);
        usort($uniqueIpRows, fn ($a, $b) => ($b['count'] <=> $a['count']) ?: ($b['last_seen']->timestamp <=> $a['last_seen']->timestamp));
        $uniqueIpRows = array_map(fn ($r) => [
            'ip' => $r['ip'],
            'provider' => $r['provider'],
            'city' => $r['city'],
            'connection_type' => $r['connection_type'],
            'count' => $r['count'],
            'last_seen_utc' => $r['last_seen']->format('Y-m-d H:i:s') . ' (UTC)',
        ], $uniqueIpRows);

        $providerRows = [];
        foreach ($providerAgg as $prov => $s) {
            $occ = (int) $s['occurrences'];
            $mob = (int) $s['mobile_occurrences'];
            $providerRows[] = [
                'provider' => $prov,
                'occurrences' => $occ,
                'unique_ips' => count($s['unique_ips']),
                'cities' => count($s['cities']),
                'mobile_occurrences' => $mob,
                'mobile_percent' => $occ > 0 ? round(($mob / $occ) * 100, 2) : 0,
                'last_seen_utc' => $s['last_seen']->format('Y-m-d H:i:s') . ' (UTC)',
            ];
        }
        usort($providerRows, fn ($a, $b) => $b['occurrences'] <=> $a['occurrences']);

        $cityRows = [];
        foreach ($cityAgg as $city => $s) {
            $occ = (int) $s['occurrences'];
            $mob = (int) $s['mobile_occurrences'];
            $cityRows[] = [
                'city' => $city,
                'occurrences' => $occ,
                'unique_ips' => count($s['unique_ips']),
                'providers' => count($s['providers']),
                'mobile_occurrences' => $mob,
                'mobile_percent' => $occ > 0 ? round(($mob / $occ) * 100, 2) : 0,
                'last_seen_utc' => $s['last_seen']->format('Y-m-d H:i:s') . ' (UTC)',
            ];
        }
        usort($cityRows, fn ($a, $b) => $b['occurrences'] <=> $a['occurrences']);

        usort($nightRows, fn ($a, $b) => strcmp($b['datetime_local'], $a['datetime_local']));
        usort($mobileRows, fn ($a, $b) => strcmp($b['datetime_local'], $a['datetime_local']));

        $periodLabel = $this->buildPeriodLabel($parsed['range_start_utc'] ?? null, $parsed['range_end_utc'] ?? null, $tz);

        return [
            'period_label' => $periodLabel,
            'total_events' => count($timelineRows),
            'total_unique_ips' => count($uniqueIpRows),

            'emails_found' => $emails,

            'timeline_rows' => $timelineRows,
            'unique_ip_rows' => $uniqueIpRows,
            'provider_stats_rows' => $providerRows,
            'city_stats_rows' => $cityRows,

            'night_total_events' => $nightTotalEvents,
            'night_events_rows' => $nightRows,

            'mobile_total_events' => $mobileTotalEvents,
            'mobile_events_rows' => $mobileRows,
        ];
    }

    private function toCarbonUtc(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) return $value->copy()->setTimezone('UTC');
        if (is_int($value)) return Carbon::createFromTimestamp($value, 'UTC');
        if (is_string($value) && trim($value) !== '') {
            try { return Carbon::parse($value, 'UTC'); } catch (\Throwable) { return null; }
        }
        return null;
    }

    private function buildPeriodLabel(mixed $startUtc, mixed $endUtc, string $tz): ?string
    {
        $start = $this->toCarbonUtc($startUtc);
        $end = $this->toCarbonUtc($endUtc);

        if (! $start || ! $end) return null;

        return $start->copy()->setTimezone($tz)->format('d/m/Y H:i:s')
            . ' até '
            . $end->copy()->setTimezone($tz)->format('d/m/Y H:i:s')
            . ' (GMT-3)';
    }
}
