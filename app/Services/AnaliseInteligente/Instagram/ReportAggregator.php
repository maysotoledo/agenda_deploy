<?php

namespace App\Services\AnaliseInteligente\Instagram;

use Carbon\Carbon;

class ReportAggregator
{
    public function buildReport(array $parsed, array $enrichedByIp): array
    {
        $tz = 'America/Belem';
        $events = $parsed['ip_events'] ?? [];

        $timelineRows = [];
        $uniqueIpAgg = [];
        $providerStatsAgg = [];
        $cityStatsAgg = [];

        $nightTotalEvents = 0;
        $nightEventsRows = [];

        $mobileTotalEvents = 0;
        $mobileEventsRows = [];

        foreach ($events as $e) {
            $ip = $e['ip'] ?? null;
            if (! $ip) {
                continue;
            }

            $timeUtc = $this->toCarbonUtc($e['time_utc'] ?? null);
            if (! $timeUtc) {
                continue;
            }

            $timeLocal = $timeUtc->copy()->setTimezone($tz);

            $info = $enrichedByIp[$ip] ?? [
                'city' => null,
                'isp' => null,
                'org' => null,
                'mobile' => null,
            ];

            $provider = trim(($info['isp'] ?? '') ?: ($info['org'] ?? ''));
            if ($provider === '') {
                $provider = 'Desconhecido';
            }

            $city = trim((string) ($info['city'] ?? ''));
            if ($city === '') {
                $city = 'Desconhecida';
            }

            $mobile = (bool) ($info['mobile'] ?? false);
            $type = $mobile ? 'Móvel' : 'Residencial';

            $timelineRows[] = [
                'datetime' => $timeLocal->format('Y-m-d H:i:s'),
                'ip' => $ip,
                'provider' => $provider,
                'city' => $city,
                'type' => $type,
            ];

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
            if ($mobile) {
                $providerStatsAgg[$provider]['mobile_occurrences']++;
            }
            if ($timeLocal->greaterThan($providerStatsAgg[$provider]['last_seen'])) {
                $providerStatsAgg[$provider]['last_seen'] = $timeLocal;
            }

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
            if ($mobile) {
                $cityStatsAgg[$city]['mobile_occurrences']++;
            }
            if ($timeLocal->greaterThan($cityStatsAgg[$city]['last_seen'])) {
                $cityStatsAgg[$city]['last_seen'] = $timeLocal;
            }

            $hour = (int) $timeLocal->format('G');
            $isNight = ($hour >= 23 || $hour <= 6);

            if ($isNight) {
                $nightTotalEvents++;
                $nightEventsRows[] = [
                    'datetime' => $timeLocal->format('Y-m-d H:i:s'),
                    'ip' => $ip,
                    'provider' => $provider,
                    'city' => $city,
                    'type' => $type,
                ];
            }

            if ($mobile) {
                $mobileTotalEvents++;
                $mobileEventsRows[] = [
                    'datetime' => $timeLocal->format('Y-m-d H:i:s'),
                    'ip' => $ip,
                    'provider' => $provider,
                    'city' => $city,
                ];
            }
        }

        usort($timelineRows, fn ($a, $b) => strcmp($b['datetime'], $a['datetime']));

        $uniqueIpRows = array_values($uniqueIpAgg);
        usort($uniqueIpRows, fn ($a, $b) => ($b['count'] <=> $a['count']) ?: strcmp($b['last_seen']->format('Y-m-d H:i:s'), $a['last_seen']->format('Y-m-d H:i:s')));
        $uniqueIpRows = array_map(fn ($r) => [
            'ip' => $r['ip'],
            'provider' => $r['provider'],
            'city' => $r['city'],
            'type' => $r['type'],
            'count' => $r['count'],
            'last_seen' => $r['last_seen']->format('Y-m-d H:i:s'),
        ], $uniqueIpRows);

        $providerStatsRows = [];
        foreach ($providerStatsAgg as $provider => $s) {
            $providerStatsRows[] = [
                'provider' => $provider,
                'occurrences' => $s['occurrences'],
                'unique_ips' => count($s['unique_ips']),
                'cities' => count($s['cities']),
                'mobile_occurrences' => $s['mobile_occurrences'],
                'mobile_percent' => $s['occurrences'] > 0 ? round(($s['mobile_occurrences'] / $s['occurrences']) * 100, 2) : 0,
                'last_seen' => $s['last_seen']->format('Y-m-d H:i:s'),
            ];
        }
        usort($providerStatsRows, fn ($a, $b) => $b['occurrences'] <=> $a['occurrences']);

        $cityStatsRows = [];
        foreach ($cityStatsAgg as $city => $s) {
            $cityStatsRows[] = [
                'city' => $city,
                'occurrences' => $s['occurrences'],
                'unique_ips' => count($s['unique_ips']),
                'providers' => count($s['providers']),
                'mobile_occurrences' => $s['mobile_occurrences'],
                'mobile_percent' => $s['occurrences'] > 0 ? round(($s['mobile_occurrences'] / $s['occurrences']) * 100, 2) : 0,
                'last_seen' => $s['last_seen']->format('Y-m-d H:i:s'),
            ];
        }
        usort($cityStatsRows, fn ($a, $b) => $b['occurrences'] <=> $a['occurrences']);

        return [
            'generated_at' => $this->formatDate($parsed['generated_at'] ?? null, $tz),
            'target' => $parsed['target'] ?? null,
            'account_identifier' => $parsed['account_identifier'] ?? null,
            'first_name' => $parsed['first_name'] ?? null,

            'registration_date' => $this->formatDate($parsed['registration_date'] ?? null, $tz),
            'registration_ip' => $parsed['registration_ip'] ?? null,
            'registration_phone' => $parsed['registration_phone'] ?? null,
            'registration_phone_formatted' => $this->formatBrazilPhone($parsed['registration_phone'] ?? null),
            'registration_phone_verified_on' => $this->formatDate($parsed['registration_phone_verified_on'] ?? null, $tz),

            'last_location_time' => $this->formatDate($parsed['last_location_time'] ?? null, $tz),
            'last_location_latitude' => $parsed['last_location_latitude'] ?? null,
            'last_location_longitude' => $parsed['last_location_longitude'] ?? null,
            'last_location_maps_url' => $parsed['last_location_maps_url'] ?? null,
            'last_location_qr_url' => $this->makeQrUrl($parsed['last_location_maps_url'] ?? null),

            'total_ips' => count($events),

            'timeline_rows' => $timelineRows,
            'unique_ip_rows' => $uniqueIpRows,
            'provider_stats_rows' => $providerStatsRows,
            'city_stats_rows' => $cityStatsRows,

            'night_total_events' => $nightTotalEvents,
            'night_events_rows' => $nightEventsRows,

            'mobile_total_events' => $mobileTotalEvents,
            'mobile_events_rows' => $mobileEventsRows,
        ];
    }

    private function toCarbonUtc(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone('UTC');
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value, 'UTC');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function formatDate(mixed $value, string $tz): ?string
    {
        $dt = $this->toCarbonUtc($value);

        if (! $dt) {
            return null;
        }

        return $dt->copy()->setTimezone($tz)->format('d/m/Y H:i:s');
    }

    private function formatBrazilPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 11) {
            return sprintf('+55 (%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
        }

        if (strlen($digits) === 10) {
            return sprintf('+55 (%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }

        return $phone;
    }

    private function makeQrUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return 'https://quickchart.io/qr?text=' . urlencode($url) . '&size=220';
    }
}
