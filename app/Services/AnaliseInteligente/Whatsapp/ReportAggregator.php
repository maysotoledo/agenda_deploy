<?php

namespace App\Services\AnaliseInteligente\Whatsapp;

use Carbon\Carbon;

class ReportAggregator
{
    public function buildReport(array $parsed, array $enrichedByIp): array
    {
        $tz = 'America/Belem';
        $events = $parsed['ip_events'] ?? [];

        $generatedAt = $this->extractGeneratedAt($parsed, $tz);
        $fileHash = $this->extractFileHash($parsed);
        $device = $this->extractDevice($parsed);
        $periodLabel = $this->extractPeriodLabel($parsed, $events, $tz);

        $symmetricContacts = array_values($parsed['symmetric_contacts'] ?? []);
        $asymmetricContacts = array_values($parsed['asymmetric_contacts'] ?? []);

        $symmetricContactsCount = count($symmetricContacts) > 0
            ? count($symmetricContacts)
            : $this->extractSymmetricContactsCount($parsed);

        $asymmetricContactsCount = count($asymmetricContacts) > 0
            ? count($asymmetricContacts)
            : $this->extractAsymmetricContactsCount($parsed);

        $groupsRows = $this->buildGroupsRows($parsed['groups'] ?? [], $tz);
        $connectionSummary = $this->buildConnectionSummary($parsed['connection_info'] ?? [], $tz);

        // agenda p/ badge
        $agendaPhones = [];
        foreach (array_merge($symmetricContacts, $asymmetricContacts) as $p) {
            $agendaPhones[(string) $p] = true;
        }

        // ✅ bilhetagem cards no formato do Blade
        $bilhetagemCards = $this->buildBilhetagemCards($parsed['message_log'] ?? [], $agendaPhones, $tz);

        // --- restante do seu código de IPs (mantido) ---
        $timelineRows = [];
        $uniqueIpAgg = [];
        $providerStatsAgg = [];
        $cityStatsAgg = [];
        $providerIpMap = [];

        $nightTotalEvents = 0;
        $nightEventsRows = [];

        $mobileTotalEvents = 0;
        $mobileEventsRows = [];

        foreach ($events as $e) {
            $ipBase = $e['ip'] ?? null;
            if (! $ipBase) continue;

            $ipDisplay = $e['ip_with_port'] ?? $ipBase;

            $timeUtc = $this->toCarbonUtc($e['time_utc'] ?? null);
            if (! $timeUtc) continue;

            $timeLocal = $timeUtc->copy()->setTimezone($tz);

            $info = $enrichedByIp[$ipBase] ?? ['city' => null, 'isp' => null, 'org' => null, 'mobile' => null];

            $provider = trim(($info['isp'] ?? '') ?: ($info['org'] ?? ''));
            $provider = preg_replace('/\s+/u', ' ', $provider ?? '') ?? '';
            if ($provider === '') $provider = 'Desconhecido';

            $city = trim((string) ($info['city'] ?? ''));
            $city = preg_replace('/\s+/u', ' ', $city ?? '') ?? '';
            if ($city === '') $city = 'Desconhecida';

            $mobile = (bool) ($info['mobile'] ?? false);
            $type = $mobile ? 'Móvel' : 'Residencial';

            $timelineRows[] = [
                'datetime' => $timeLocal->format('Y-m-d H:i:s'),
                'ip' => $ipDisplay,
                'provider' => $provider,
                'city' => $city,
                'type' => $type,
            ];

            $uniqueIpAgg[$ipBase] ??= [
                'ip' => $ipBase,
                'provider' => $provider,
                'city' => $city,
                'type' => $type,
                'count' => 0,
                'last_seen' => $timeLocal,
            ];
            $uniqueIpAgg[$ipBase]['count']++;
            if ($timeLocal->greaterThan($uniqueIpAgg[$ipBase]['last_seen'])) {
                $uniqueIpAgg[$ipBase]['last_seen'] = $timeLocal;
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
            $providerStatsAgg[$provider]['unique_ips'][$ipBase] = true;
            $providerStatsAgg[$provider]['cities'][$city] = true;
            if ($mobile) $providerStatsAgg[$provider]['mobile_occurrences']++;
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
            $cityStatsAgg[$city]['unique_ips'][$ipBase] = true;
            $cityStatsAgg[$city]['providers'][$provider] = true;
            if ($mobile) $cityStatsAgg[$city]['mobile_occurrences']++;
            if ($timeLocal->greaterThan($cityStatsAgg[$city]['last_seen'])) {
                $cityStatsAgg[$city]['last_seen'] = $timeLocal;
            }

            $providerIpMap[$provider] ??= [];
            $providerIpMap[$provider][$ipDisplay] ??= [
                'ip' => $ipDisplay,
                'count' => 0,
                'last_seen' => $timeLocal,
                'city' => $city,
                'mobile' => $mobile,
            ];
            $providerIpMap[$provider][$ipDisplay]['count']++;
            if ($timeLocal->greaterThan($providerIpMap[$provider][$ipDisplay]['last_seen'])) {
                $providerIpMap[$provider][$ipDisplay]['last_seen'] = $timeLocal;
            }

            $hour = (int) $timeLocal->format('G');
            $isNight = ($hour >= 23 || $hour <= 6);

            if ($isNight) {
                $nightTotalEvents++;
                $nightEventsRows[] = [
                    'datetime' => $timeLocal->format('Y-m-d H:i:s'),
                    'ip' => $ipDisplay,
                    'provider' => $provider,
                    'city' => $city,
                    'type' => $type,
                ];
            }

            if ($mobile) {
                $mobileTotalEvents++;
                $mobileEventsRows[] = [
                    'datetime' => $timeLocal->format('Y-m-d H:i:s'),
                    'ip' => $ipDisplay,
                    'provider' => $provider,
                    'city' => $city,
                ];
            }
        }

        usort($timelineRows, fn ($a, $b) => strcmp($b['datetime'], $a['datetime']));

        $uniqueIpRows = array_values($uniqueIpAgg);
        usort($uniqueIpRows, fn ($a, $b) => ($b['count'] <=> $a['count']) ?: ($b['last_seen']->timestamp <=> $a['last_seen']->timestamp));
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

        $providerIpMapOut = [];
        foreach ($providerIpMap as $prov => $ipsAssoc) {
            $list = array_values($ipsAssoc);
            usort($list, fn ($a, $b) => ($b['count'] <=> $a['count']) ?: ($b['last_seen']->timestamp <=> $a['last_seen']->timestamp));

            $providerIpMapOut[$prov] = array_map(fn ($r) => [
                'ip' => $r['ip'],
                'count' => (int) $r['count'],
                'last_seen' => $r['last_seen']->format('Y-m-d H:i:s'),
                'city' => $r['city'] ?? '-',
                'connection_type' => ($r['mobile'] ?? false) ? 'Móvel' : 'Residencial',
            ], $list);
        }

        usort($nightEventsRows, fn ($a, $b) => strcmp($b['datetime'], $a['datetime']));
        usort($mobileEventsRows, fn ($a, $b) => strcmp($b['datetime'], $a['datetime']));

        return [
            'generated_at' => $generatedAt,
            'file_hash' => $fileHash,
            'target' => $parsed['target'] ?? null,
            'total_ips' => count($events),
            'device' => $device,
            'period_label' => $periodLabel,

            'symmetric_contacts_count' => $symmetricContactsCount,
            'asymmetric_contacts_count' => $asymmetricContactsCount,
            'symmetric_contacts' => $symmetricContacts,
            'asymmetric_contacts' => $asymmetricContacts,

            'groups_rows' => $groupsRows,
            'connection_summary' => $connectionSummary,

            // ✅ aqui é o que o Blade usa
            'bilhetagem_cards' => $bilhetagemCards,

            'timeline_rows' => $timelineRows,
            'unique_ip_rows' => $uniqueIpRows,
            'provider_stats_rows' => $providerStatsRows,
            'city_stats_rows' => $cityStatsRows,
            'provider_ip_map' => $providerIpMapOut,

            'night_total_events' => $nightTotalEvents,
            'night_events_rows' => $nightEventsRows,

            'mobile_total_events' => $mobileTotalEvents,
            'mobile_events_rows' => $mobileEventsRows,

            'mobile_top' => [],
            'mobile_recent_provider' => null,
            'mobile_recent_ips' => [],
            'fixed_night_top' => [],
            'fixed_recent_provider' => null,
            'fixed_recent_ips' => [],
        ];
    }

    private function buildBilhetagemCards(array $messageLog, array $agendaPhones, string $tz): array
    {
        $byRecipient = [];

        foreach ($messageLog as $m) {
            $m = (array) $m;

            $recipient = trim((string) ($m['recipient'] ?? ''));
            if ($recipient === '') continue;

            $tsUtc = $this->toCarbonUtc($m['timestamp_utc'] ?? null);
            $tsLocal = $tsUtc ? $tsUtc->copy()->setTimezone($tz)->format('Y-m-d H:i:s') : null;

            $row = [
                'timestamp' => $tsLocal,
                'sender_ip' => ($m['sender_ip'] ?? null) ?: null,
                'sender_port' => ($m['sender_port'] ?? null) ?: null,
                'type' => ($m['type'] ?? null) ?: null,
                'message_id' => ($m['message_id'] ?? null) ?: null,
            ];

            $byRecipient[$recipient] ??= [
                'recipient' => $recipient,
                'in_agenda' => isset($agendaPhones[$recipient]),
                'total' => 0,
                'rows' => [],
            ];

            $byRecipient[$recipient]['total']++;
            $byRecipient[$recipient]['rows'][] = $row;
        }

        if (! $byRecipient) return [];

        $cards = [];
        foreach ($byRecipient as $recipient => $data) {
            $rows = $data['rows'];

            usort($rows, fn ($a, $b) => strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? '')));

            $latest = $rows[0] ?? null;
            $others = array_slice($rows, 1, 10);

            $cards[] = [
                'recipient' => $recipient,
                'in_agenda' => (bool) ($data['in_agenda'] ?? false),
                'total' => (int) ($data['total'] ?? 0),
                'latest' => $latest,
                'others' => $others,
            ];
        }

        usort($cards, fn ($a, $b) =>
            ($b['total'] <=> $a['total'])
            ?: strcmp((string) data_get($b, 'latest.timestamp', ''), (string) data_get($a, 'latest.timestamp', ''))
        );

        return $cards;
    }

    private function buildGroupsRows(array $groups, string $tz): array
    {
        $rows = [];

        $owned = is_array($groups['owned'] ?? null) ? $groups['owned'] : [];
        $part = is_array($groups['participating'] ?? null) ? $groups['participating'] : [];

        $push = function (array $g, string $tipo) use (&$rows, $tz) {
            $createdUtc = $g['creation_utc'] ?? null;
            $createdLocal = null;

            if ($createdUtc instanceof Carbon) {
                $createdLocal = $createdUtc->copy()->setTimezone($tz)->format('Y-m-d H:i:s');
            }

            $rows[] = [
                'tipo' => $tipo,
                'id' => $g['id'] ?? null,
                'criacao' => $createdLocal,
                'membros' => is_numeric($g['size'] ?? null) ? (int) $g['size'] : null,
                'assunto' => $g['subject'] ?? null,
                'descricao' => $g['description'] ?? null,
            ];
        };

        foreach ($owned as $g) $push((array) $g, 'Criado (Owned)');
        foreach ($part as $g) $push((array) $g, 'Participa');

        usort($rows, fn ($a, $b) => strcmp((string) ($b['criacao'] ?? ''), (string) ($a['criacao'] ?? '')));

        return $rows;
    }

    private function buildConnectionSummary(array $conn, string $tz): array
    {
        $serviceStartLocal = null;
        if (($conn['service_start_utc'] ?? null) instanceof Carbon) {
            $serviceStartLocal = $conn['service_start_utc']->copy()->setTimezone($tz)->format('Y-m-d H:i:s');
        }

        $lastSeenLocal = null;
        if (($conn['last_seen_utc'] ?? null) instanceof Carbon) {
            $lastSeenLocal = $conn['last_seen_utc']->copy()->setTimezone($tz)->format('Y-m-d H:i:s');
        }

        return array_filter([
            'device_id' => $conn['device_id'] ?? null,
            'service_start' => $serviceStartLocal,
            'device_type' => $conn['device_type'] ?? null,
            'app_version' => $conn['app_version'] ?? null,
            'device_os_build_number' => $conn['device_os_build_number'] ?? null,
            'connection_state' => $conn['connection_state'] ?? null,
            'last_seen' => $lastSeenLocal,
            'last_ip' => $conn['last_ip'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
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

    private function extractGeneratedAt(array $parsed, string $tz): ?string
    {
        foreach ([
            $parsed['generated_at'] ?? null,
            $parsed['generatedAt'] ?? null,
            $parsed['meta']['generated_at'] ?? null,
            $parsed['summary']['generated_at'] ?? null,
        ] as $value) {
            $dt = $this->toCarbonUtc($value);
            if ($dt) return $dt->copy()->setTimezone($tz)->format('d/m/Y - H:i:s');
            if (is_string($value) && trim($value) !== '') return trim($value);
        }
        return null;
    }

    private function extractFileHash(array $parsed): ?string
    {
        foreach ([
            $parsed['file_hash'] ?? null,
            $parsed['sha256'] ?? null,
            $parsed['hash'] ?? null,
            $parsed['meta']['file_hash'] ?? null,
            $parsed['meta']['sha256'] ?? null,
            $parsed['summary']['file_hash'] ?? null,
        ] as $value) {
            if (is_string($value) && trim($value) !== '') return trim($value);
        }
        return null;
    }

    private function extractDevice(array $parsed): ?string
    {
        foreach ([
            $parsed['device'] ?? null,
            $parsed['device_name'] ?? null,
            $parsed['device_info'] ?? null,
            $parsed['summary']['device'] ?? null,
        ] as $value) {
            if (is_string($value) && trim($value) !== '') return trim($value);

            if (is_array($value)) {
                $parts = array_filter([
                    $value['brand'] ?? null,
                    $value['model'] ?? null,
                    $value['name'] ?? null,
                    $value['device'] ?? null,
                ], fn ($v) => is_string($v) && trim($v) !== '');

                if ($parts) return implode(' - ', array_unique(array_map('trim', $parts)));
            }
        }
        return null;
    }

    private function extractPeriodLabel(array $parsed, array $events, string $tz): ?string
    {
        $start = $this->toCarbonUtc($parsed['range_start_utc'] ?? null);
        $end = $this->toCarbonUtc($parsed['range_end_utc'] ?? null);

        if (! $start || ! $end) {
            $times = [];
            foreach ($events as $e) {
                $dt = $this->toCarbonUtc($e['time_utc'] ?? null);
                if ($dt) $times[] = $dt;
            }
            if ($times) {
                usort($times, fn ($a, $b) => $a->timestamp <=> $b->timestamp);
                $start = $start ?: $times[0];
                $end = $end ?: $times[count($times) - 1];
            }
        }

        if ($start && $end) {
            return $start->copy()->setTimezone($tz)->format('d/m/Y H:i:s')
                . ' até '
                . $end->copy()->setTimezone($tz)->format('d/m/Y H:i:s');
        }

        return null;
    }

    private function extractSymmetricContactsCount(array $parsed): int
    {
        foreach ([
            $parsed['symmetric_contacts_count'] ?? null,
            $parsed['symmetric_contacts_total'] ?? null,
        ] as $value) {
            if (is_numeric($value)) return (int) $value;
        }
        return is_array($parsed['symmetric_contacts'] ?? null) ? count($parsed['symmetric_contacts']) : 0;
    }

    private function extractAsymmetricContactsCount(array $parsed): int
    {
        foreach ([
            $parsed['asymmetric_contacts_count'] ?? null,
            $parsed['asymmetric_contacts_total'] ?? null,
        ] as $value) {
            if (is_numeric($value)) return (int) $value;
        }
        return is_array($parsed['asymmetric_contacts'] ?? null) ? count($parsed['asymmetric_contacts']) : 0;
    }
}
