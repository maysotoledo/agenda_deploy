<?php

namespace App\Services\AnaliseInteligente;

use Carbon\Carbon;

class ReportAggregator
{
    public function buildReport(array $parsed, array $enrichedByIp): array
    {
        $tz = 'America/Belem';

        $events = $parsed['ip_events'] ?? [];
        $totalOcorrencias = count($events);

        // Para abas Residencial/Móvel
        $providerCountsMobile = [];
        $providerCountsFixedNight = [];
        $recentFixed = [];
        $recentMobile = [];

        // Drill-down: provedor -> IPs
        $providerIpMap = [];

        // Planilhas investigação
        $timelineRows = [];
        $uniqueIpAgg = [];       // ip => stats
        $providerStatsAgg = [];  // provider => stats
        $cityStatsAgg = [];      // city => stats

        foreach ($events as $e) {
            $ip = $e['ip'] ?? null;
            if (! $ip) continue;

            $timeUtc = $this->toCarbonUtc($e['time_utc'] ?? null);
            if (! $timeUtc) continue;

            $timeLocal = $timeUtc->copy()->setTimezone($tz);

            $info = $enrichedByIp[$ip] ?? ['city' => null, 'isp' => null, 'org' => null, 'mobile' => null];

            $provider = trim(($info['isp'] ?? '') ?: ($info['org'] ?? ''));
            if ($provider === '') $provider = 'Desconhecido';

            $city = trim((string) ($info['city'] ?? ''));
            if ($city === '') $city = 'Desconhecida';

            $mobile = (bool) ($info['mobile'] ?? false);
            $type = $mobile ? 'Móvel' : 'Residencial';

            // 1) Timeline
            $timelineRows[] = [
                'datetime' => $timeLocal->format('Y-m-d H:i:s'),
                'ip' => $ip,
                'provider' => $provider,
                'city' => $city,
                'type' => $type,
            ];

            // 2) IPs únicos (agregado)
            $uniqueIpAgg[$ip] ??= [
                'ip' => $ip,
                'provider' => $provider,
                'city' => $city,
                'type' => $type,
                'count' => 0,
                'last_seen' => $timeLocal,
            ];
            $uniqueIpAgg[$ip]['count']++;
            if ($timeLocal->greaterThan($uniqueIpAgg[$ip]['last_seen'])) {
                $uniqueIpAgg[$ip]['last_seen'] = $timeLocal;
            }

            // 3) Provedores (métricas)
            $providerStatsAgg[$provider] ??= [
                'provider' => $provider,
                'occurrences' => 0,
                'unique_ips' => [],
                'cities' => [],
                'mobile_occurrences' => 0,
                'last_seen' => $timeLocal,
            ];
            $providerStatsAgg[$provider]['occurrences']++;
            $providerStatsAgg[$provider]['unique_ips'][$ip] = true;
            $providerStatsAgg[$provider]['cities'][$city] = true;
            if ($mobile) $providerStatsAgg[$provider]['mobile_occurrences']++;
            if ($timeLocal->greaterThan($providerStatsAgg[$provider]['last_seen'])) {
                $providerStatsAgg[$provider]['last_seen'] = $timeLocal;
            }

            // 4) Cidades (métricas)
            $cityStatsAgg[$city] ??= [
                'city' => $city,
                'occurrences' => 0,
                'unique_ips' => [],
                'providers' => [],
                'mobile_occurrences' => 0,
                'last_seen' => $timeLocal,
            ];
            $cityStatsAgg[$city]['occurrences']++;
            $cityStatsAgg[$city]['unique_ips'][$ip] = true;
            $cityStatsAgg[$city]['providers'][$provider] = true;
            if ($mobile) $cityStatsAgg[$city]['mobile_occurrences']++;
            if ($timeLocal->greaterThan($cityStatsAgg[$city]['last_seen'])) {
                $cityStatsAgg[$city]['last_seen'] = $timeLocal;
            }

            // Residencial/Móvel (para abas)
            if ($mobile) {
                $providerCountsMobile[$provider] = ($providerCountsMobile[$provider] ?? 0) + 1;
                $recentMobile[] = ['ip' => $ip, 'dt' => $timeLocal, 'provider' => $provider];
            } else {
                $recentFixed[] = ['ip' => $ip, 'dt' => $timeLocal, 'provider' => $provider];

                $hour = (int) $timeLocal->format('G');
                if ($hour >= 23 || $hour <= 6) {
                    $providerCountsFixedNight[$provider] = ($providerCountsFixedNight[$provider] ?? 0) + 1;
                }
            }

            // Drill-down (modal): provedor -> IPs agregados
            $providerIpMap[$provider] ??= [];
            $providerIpMap[$provider][$ip] ??= [
                'ip' => $ip,
                'count' => 0,
                'last_seen' => $timeLocal,
                'city' => $city,
                'mobile' => $mobile,
            ];
            $providerIpMap[$provider][$ip]['count']++;
            if ($timeLocal->greaterThan($providerIpMap[$provider][$ip]['last_seen'])) {
                $providerIpMap[$provider][$ip]['last_seen'] = $timeLocal;
            }
        }

        // Ordenações finais
        usort($timelineRows, fn ($a, $b) => strcmp($b['datetime'], $a['datetime']));

        $uniqueIpRows = array_values($uniqueIpAgg);
        usort(
            $uniqueIpRows,
            fn ($a, $b) => ($b['count'] <=> $a['count']) ?: ($b['last_seen']->timestamp <=> $a['last_seen']->timestamp)
        );
        $uniqueIpRows = array_map(fn ($r) => [
            'ip' => $r['ip'],
            'provider' => $r['provider'],
            'city' => $r['city'],
            'type' => $r['type'],
            'count' => $r['count'],
            'last_seen' => $r['last_seen']->format('Y-m-d H:i:s'),
        ], $uniqueIpRows);

        $providerStatsRows = [];
        foreach ($providerStatsAgg as $prov => $s) {
            $occ = (int) $s['occurrences'];
            $mob = (int) $s['mobile_occurrences'];
            $providerStatsRows[] = [
                'provider' => $prov,
                'occurrences' => $occ,
                'unique_ips' => count($s['unique_ips']),
                'cities' => count($s['cities']),
                'mobile_occurrences' => $mob,
                'mobile_percent' => $occ > 0 ? round(($mob / $occ) * 100, 2) : 0,
                'last_seen' => $s['last_seen']->format('Y-m-d H:i:s'),
            ];
        }
        usort($providerStatsRows, fn ($a, $b) => ($b['occurrences'] <=> $a['occurrences']));

        $cityStatsRows = [];
        foreach ($cityStatsAgg as $city => $s) {
            $occ = (int) $s['occurrences'];
            $mob = (int) $s['mobile_occurrences'];
            $cityStatsRows[] = [
                'city' => $city,
                'occurrences' => $occ,
                'unique_ips' => count($s['unique_ips']),
                'providers' => count($s['providers']),
                'mobile_occurrences' => $mob,
                'mobile_percent' => $occ > 0 ? round(($mob / $occ) * 100, 2) : 0,
                'last_seen' => $s['last_seen']->format('Y-m-d H:i:s'),
            ];
        }
        usort($cityStatsRows, fn ($a, $b) => ($b['occurrences'] <=> $a['occurrences']));

        // Abas Residencial/Móvel
        arsort($providerCountsFixedNight);
        arsort($providerCountsMobile);

        usort($recentFixed, fn ($a, $b) => $b['dt']->timestamp <=> $a['dt']->timestamp);
        usort($recentMobile, fn ($a, $b) => $b['dt']->timestamp <=> $a['dt']->timestamp);

        $fixedNightTop = $this->asRankedList($providerCountsFixedNight);
        $mobileTop = $this->asRankedList($providerCountsMobile);

        $fixedProviderForRecent = $fixedNightTop[0]['name'] ?? null;
        $mobileProviderForRecent = $mobileTop[0]['name'] ?? null;

        $recentFixedFiltered = $fixedProviderForRecent
            ? array_values(array_filter($recentFixed, fn ($r) => $r['provider'] === $fixedProviderForRecent))
            : $recentFixed;

        $recentMobileFiltered = $mobileProviderForRecent
            ? array_values(array_filter($recentMobile, fn ($r) => $r['provider'] === $mobileProviderForRecent))
            : $recentMobile;

        // provider_ip_map (modal)
        $providerIpMapOut = [];
        foreach ($providerIpMap as $prov => $ipsAssoc) {
            $list = array_values($ipsAssoc);

            usort($list, function ($a, $b) {
                $c = ($b['count'] <=> $a['count']);
                if ($c !== 0) return $c;
                return $b['last_seen']->timestamp <=> $a['last_seen']->timestamp;
            });

            $providerIpMapOut[$prov] = array_map(function ($r) {
                return [
                    'ip' => $r['ip'],
                    'count' => $r['count'],
                    'last_seen' => $r['last_seen']->format('Y-m-d H:i:s'),
                    'city' => $r['city'] ?? '-',
                    'connection_type' => ($r['mobile'] ?? false) ? 'Móvel' : 'Residencial',
                ];
            }, $list);
        }

        return [
            // Resumo
            'target' => $parsed['target'] ?? null,
            'total_ips' => $totalOcorrencias,
            'device' => $this->formatDevice($parsed['device_build'] ?? null),
            'period_local' => $this->formatPeriod($parsed['range_start_utc'] ?? null, $parsed['range_end_utc'] ?? null, $tz),
            'symmetric_contacts' => $parsed['symmetric_contacts_total'] ?? null,
            'asymmetric_contacts' => $parsed['asymmetric_contacts_total'] ?? null,

            // Planilhas principais
            'timeline_rows' => $timelineRows,
            'unique_ip_rows' => $uniqueIpRows,
            'provider_stats_rows' => $providerStatsRows,
            'city_stats_rows' => $cityStatsRows,

            // Residencial
            'fixed_night_top' => array_slice($fixedNightTop, 0, 50),
            'fixed_recent_provider' => $fixedProviderForRecent,
            'fixed_recent_ips' => $this->formatRecent(array_slice($recentFixedFiltered, 0, 50), 'GMT-3'),

            // Móvel
            'mobile_top' => array_slice($mobileTop, 0, 50),
            'mobile_recent_provider' => $mobileProviderForRecent,
            'mobile_recent_ips' => $this->formatRecent(array_slice($recentMobileFiltered, 0, 50), 'GMT-3'),

            // Drill-down
            'provider_ip_map' => $providerIpMapOut,
        ];
    }

    private function toCarbonUtc(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) return $value->copy()->setTimezone('UTC');
        if (is_int($value)) return Carbon::createFromTimestamp($value, 'UTC');
        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value, 'UTC');
            } catch (\Throwable) {
                return null;
            }
        }
        return null;
    }

    private function asRankedList(array $counts): array
    {
        $out = [];
        foreach ($counts as $name => $count) {
            $out[] = ['name' => $name, 'count' => $count];
        }
        return $out;
    }

    private function formatPeriod(mixed $startUtc, mixed $endUtc, string $tz): ?string
    {
        $start = $this->toCarbonUtc($startUtc);
        $end = $this->toCarbonUtc($endUtc);

        if (! $start || ! $end) return null;

        return $start->copy()->setTimezone($tz)->format('d/m/Y H:i:s')
            . ' até '
            . $end->copy()->setTimezone($tz)->format('d/m/Y H:i:s')
            . ' (GMT-3)';
    }

    private function formatDevice(?string $deviceBuild): ?string
    {
        if (! $deviceBuild) return null;

        if (preg_match('/model:\s*(.+)$/i', $deviceBuild, $m)) {
            $model = trim($m[1]);
            return $model !== '' ? $model : $deviceBuild;
        }

        return $deviceBuild;
    }

    private function formatRecent(array $rows, string $tzLabel): array
    {
        return array_map(function ($r) use ($tzLabel) {
            /** @var Carbon $dt */
            $dt = $r['dt'];

            return [
                'ip' => $r['ip'],
                'datetime' => $dt->format('d/m/Y H:i:s'),
                'tz' => $tzLabel,
            ];
        }, $rows);
    }
}
