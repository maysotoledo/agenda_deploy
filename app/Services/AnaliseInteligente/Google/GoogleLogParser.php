<?php

namespace App\Services\AnaliseInteligente\Google;

use App\Services\AnaliseInteligente\Platform\PlatformLogParser;
use Carbon\Carbon;

class GoogleLogParser extends PlatformLogParser
{
    public function __construct()
    {
        parent::__construct('google', 'Google');
    }

    protected function extractExtraEvents(string $rawText, string $text): array
    {
        return $this->extractSubscriberEvents($rawText);
    }

    protected function shouldExtractTextEvents(string $rawText, string $text, array $extraEvents): bool
    {
        if (! empty($extraEvents)) {
            return false;
        }

        $rawLower = mb_strtolower($rawText);

        if (
            str_contains($rawLower, 'google subscriber information')
            || str_contains($rawLower, 'googleaccount.subscriberinfo')
            || str_contains($rawLower, 'myactivity.myactivity')
            || str_contains($rawLower, 'mdl-typography--title">maps')
            || str_contains($rawLower, 'mdl-typography--title">search')
            || str_contains($rawLower, 'my activity/maps/')
            || str_contains($rawLower, 'my activity/search/')
            || str_contains($rawLower, 'my activity/discover/')
        ) {
            return false;
        }

        return true;
    }

    protected function extractExtraParsedData(string $rawText, string $text, array $events): array
    {
        $subscriberInfo = $this->extractSubscriberInfo($rawText, $text);
        $mapsRows = $this->extractMapsRows($rawText);
        $searchRows = $this->extractSearchRows($rawText);

        return array_filter([
            'google_subscriber_info' => $subscriberInfo,
            'maps_rows' => $mapsRows,
            'search_rows' => $searchRows,
        ], fn ($value) => $value !== null && $value !== []);
    }

    protected function extractIdentifiers(string $text): array
    {
        return $this->mergeIdentifiers(parent::extractIdentifiers($text), [
            'Android ID' => '/\b(?:android id|android_id)\s*[:#-]?\s*([a-f0-9]{8,32})/i',
            'GAIA' => '/\b(?:gaia|google id)\s*[:#-]?\s*([A-Z0-9._-]{5,})/i',
        ], $text);
    }

    private function extractSubscriberEvents(string $raw): array
    {
        if (stripos($raw, 'IP ACTIVITY') === false) {
            return [];
        }

        $events = [];

        if (! preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $raw, $rows)) {
            return [];
        }

        foreach ($rows[1] as $rowHtml) {
            if (! preg_match_all('/<td\b[^>]*>(.*?)<\/td>/is', $rowHtml, $cells)) {
                continue;
            }

            $values = array_map(
                fn (string $value): string => trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                $cells[1],
            );

            if (count($values) < 2) {
                continue;
            }

            $timestamp = $values[0] ?? '';
            $ip = $values[1] ?? '';

            if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                continue;
            }

            $date = $this->parseDate($timestamp, $this->extractTzLabel($timestamp) ?? 'UTC');
            if (! $date) {
                continue;
            }

            $activity = $values[2] ?? null;
            $androidId = $values[3] ?? null;
            $iosId = $values[4] ?? null;
            $userAgent = $values[5] ?? null;

            $descriptionParts = array_filter([
                $activity ? "Atividade: {$activity}" : null,
                $androidId ? "Android ID: {$androidId}" : null,
                $iosId ? "Apple iOS IDFV: {$iosId}" : null,
                $userAgent ? "User-Agent: {$userAgent}" : null,
            ]);

            $events[] = [
                'time_utc' => $date,
                'tz_label' => 'UTC',
                'ip' => $ip,
                'logical_port' => null,
                'action' => $activity ?: null,
                'description' => implode(' | ', $descriptionParts) ?: 'Google IP Activity',
                'android_id' => $androidId ?: null,
                'ios_idfv' => $iosId ?: null,
                'user_agent' => $userAgent ?: null,
            ];
        }

        return $events;
    }

    private function extractMapsRows(string $raw): array
    {
        if (stripos($raw, 'Maps') === false || stripos($raw, 'content-cell') === false) {
            return [];
        }

        $rows = [];
        $blocks = preg_split('/<div class="outer-cell\b[^"]*[^>]*>/i', $raw) ?: [];

        foreach ($blocks as $block) {
            if (stripos($block, 'mdl-typography--title">Maps') === false) {
                continue;
            }

            if (! preg_match('/<div class="content-cell\b(?:(?!mdl-typography--text-right).)*?mdl-typography--body-1[^"]*">(.*?)<\/div>/is', $block, $match)) {
                continue;
            }

            $contentHtml = $match[1];
            $contentText = $this->normalizeActivityText($contentHtml);
            $dateUtc = $this->extractMapsDate($contentText);

            if (! $dateUtc) {
                continue;
            }

            $links = $this->extractLinks($contentHtml);
            $firstLink = $links[0] ?? null;
            $firstLinkText = trim((string) ($firstLink['text'] ?? ''));
            $firstHref = trim((string) ($firstLink['href'] ?? ''));

            if (preg_match('/^Directions to\s+([^\n]+)/iu', $contentText, $directionMatch)) {
                $destination = $firstLinkText !== '' ? $firstLinkText : trim($directionMatch[1]);
                $origin = $this->extractCurrentLocation($contentText);

                $rows[] = $this->buildMapsRow(
                    type: 'Rota',
                    target: $destination,
                    origin: $origin,
                    summary: 'Alvo foi da sua localizacao atual' . ($origin ? " ({$origin})" : '') . " para {$destination}.",
                    dateUtc: $dateUtc,
                    mapsUrl: $this->buildRouteOriginUrl($origin, $destination, $firstHref),
                );

                continue;
            }

            if ($firstLinkText !== '') {
                $rows[] = $this->buildMapsRow(
                    type: 'Pesquisa',
                    target: $firstLinkText,
                    origin: null,
                    summary: "Alvo pesquisou {$firstLinkText}.",
                    dateUtc: $dateUtc,
                    mapsUrl: $this->normalizeMapsUrl($firstHref, $firstLinkText),
                );

                continue;
            }

            $activity = trim(preg_replace('/\s+' . preg_quote($this->extractMapsDateText($contentText) ?? '', '/') . '$/u', '', $contentText) ?? $contentText);
            if ($activity !== '') {
                $rows[] = $this->buildMapsRow(
                    type: 'Atividade',
                    target: $activity,
                    origin: null,
                    summary: "Atividade no Google Maps: {$activity}.",
                    dateUtc: $dateUtc,
                    mapsUrl: null,
                );
            }
        }

        $rows = $this->deduplicateMapsRows($rows);
        usort($rows, fn (array $a, array $b): int => ((int) ($b['datetime_ts'] ?? 0)) <=> ((int) ($a['datetime_ts'] ?? 0)));

        return $rows;
    }

    private function buildMapsRow(string $type, string $target, ?string $origin, string $summary, Carbon $dateUtc, ?string $mapsUrl): array
    {
        return [
            'type' => $type,
            'summary' => $summary,
            'target' => $target,
            'origin' => $origin,
            'datetime_ts' => $dateUtc->timestamp,
            'datetime_utc' => $dateUtc->format('d/m/Y H:i:s') . ' (UTC)',
            'datetime_local' => $dateUtc->copy()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') . ' (GMT-3)',
            'maps_url' => $mapsUrl,
        ];
    }

    private function extractSearchRows(string $raw): array
    {
        if (stripos($raw, 'Search') === false || stripos($raw, 'Searched for') === false) {
            return [];
        }

        $rows = [];
        $blocks = preg_split('/<div class="outer-cell\b[^"]*[^>]*>/i', $raw) ?: [];

        foreach ($blocks as $block) {
            if (stripos($block, 'mdl-typography--title">Search') === false) {
                continue;
            }

            if (! preg_match('/<div class="content-cell\b(?:(?!mdl-typography--text-right).)*?mdl-typography--body-1[^"]*">(.*?)<\/div>/is', $block, $match)) {
                continue;
            }

            $contentHtml = $match[1];
            $contentText = $this->normalizeActivityText($contentHtml);

            if (! preg_match('/^Searched for\s+(.+)$/imu', $contentText)) {
                continue;
            }

            $dateUtc = $this->extractMapsDate($contentText);
            if (! $dateUtc) {
                continue;
            }

            $links = $this->extractLinks($contentHtml);
            $firstLink = $links[0] ?? null;
            $query = trim((string) ($firstLink['text'] ?? ''));
            $href = trim((string) ($firstLink['href'] ?? ''));

            if ($query === '' && preg_match('/^Searched for\s+([^\n]+)/imu', $contentText, $queryMatch)) {
                $query = trim($queryMatch[1]);
            }

            if ($query === '') {
                continue;
            }

            $rows[] = [
                'query' => $query,
                'summary' => "Alvo pesquisou {$query}.",
                'datetime_ts' => $dateUtc->timestamp,
                'datetime_local' => $dateUtc->copy()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') . ' (GMT-3)',
                'search_url' => $this->normalizeSearchUrl($href, $query),
            ];
        }

        $rows = $this->deduplicateSearchRows($rows);
        usort($rows, fn (array $a, array $b): int => ((int) ($b['datetime_ts'] ?? 0)) <=> ((int) ($a['datetime_ts'] ?? 0)));

        return $rows;
    }

    private function normalizeActivityText(string $html): string
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\xC2\xA0", 'Â ', 'Â', "\u{202F}"], ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\n\s+/u', "\n", $text) ?? $text;

        return trim($text);
    }

    private function extractLinks(string $html): array
    {
        if (! preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        return array_map(fn (array $match): array => [
            'href' => html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'text' => trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        ], $matches);
    }

    private function extractMapsDate(string $text): ?Carbon
    {
        $dateText = $this->extractMapsDateText($text);
        if (! $dateText) {
            return null;
        }

        try {
            return Carbon::parse($dateText, 'UTC')->setTimezone('UTC');
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractMapsDateText(string $text): ?string
    {
        $text = str_replace(["\xC2\xA0", "\u{202F}", 'Â', 'â€¯'], ' ', $text);

        if (preg_match('/\b[A-Z][a-z]{2}\s+\d{1,2},\s+\d{4},\s+\d{1,2}:\d{2}:\d{2}\s*(?:AM|PM)\s+UTC\b/u', $text, $match)) {
            return trim($match[0]);
        }

        return null;
    }

    private function extractCurrentLocation(string $text): ?string
    {
        if (preg_match('/Current location\s+(-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?)/iu', $text, $match)) {
            return preg_replace('/\s+/', '', $match[1]) ?: trim($match[1]);
        }

        return null;
    }

    private function normalizeMapsUrl(?string $url, string $query): ?string
    {
        $url = trim((string) $url);

        if ($url !== '') {
            return str_starts_with($url, 'http') ? $url : 'https://www.google.com' . (str_starts_with($url, '/') ? $url : '/' . $url);
        }

        $query = trim($query);

        return $query !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query) : null;
    }

    private function normalizeSearchUrl(?string $url, string $query): ?string
    {
        $url = trim((string) $url);

        if ($url !== '') {
            return str_starts_with($url, 'http') ? $url : 'https://www.google.com' . (str_starts_with($url, '/') ? $url : '/' . $url);
        }

        $query = trim($query);

        return $query !== '' ? 'https://www.google.com/search?q=' . rawurlencode($query) : null;
    }

    private function buildRouteOriginUrl(?string $origin, string $destination, ?string $fallbackUrl): ?string
    {
        $origin = trim((string) $origin);
        $destination = trim($destination);

        if ($origin !== '') {
            return $this->buildMapsPlaceUrl($origin) ?? 'https://www.google.com.br/maps/search/?api=1&query=' . rawurlencode($origin);
        }

        return $this->normalizeMapsUrl($fallbackUrl, $destination);
    }

    private function buildMapsPlaceUrl(string $coordinates): ?string
    {
        if (! preg_match('/^(?<lat>-?\d+(?:\.\d+)?),(?<lng>-?\d+(?:\.\d+)?)$/', trim($coordinates), $match)) {
            return null;
        }

        $lat = (float) $match['lat'];
        $lng = (float) $match['lng'];
        $query = rawurlencode($this->decimalToDms($lat, true) . ' ' . $this->decimalToDms($lng, false));

        return "https://www.google.com.br/maps/place/{$query}/@{$lat},{$lng},17z";
    }

    private function decimalToDms(float $value, bool $isLatitude): string
    {
        $direction = $isLatitude
            ? ($value < 0 ? 'S' : 'N')
            : ($value < 0 ? 'W' : 'E');

        $absolute = abs($value);
        $degrees = (int) floor($absolute);
        $minutesFloat = ($absolute - $degrees) * 60;
        $minutes = (int) floor($minutesFloat);
        $seconds = ($minutesFloat - $minutes) * 60;

        return sprintf('%d°%02d\'%04.1f"%s', $degrees, $minutes, $seconds, $direction);
    }

    private function deduplicateMapsRows(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            $key = md5(($row['type'] ?? '') . '|' . ($row['target'] ?? '') . '|' . ($row['origin'] ?? '') . '|' . ($row['datetime_utc'] ?? ''));
            $out[$key] = $row;
        }

        return array_values($out);
    }

    private function deduplicateSearchRows(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            $key = md5(($row['query'] ?? '') . '|' . ($row['datetime_ts'] ?? ''));
            $out[$key] = $row;
        }

        return array_values($out);
    }

    private function extractSubscriberInfo(string $raw, string $text): ?array
    {
        if (stripos($raw, 'GOOGLE SUBSCRIBER INFORMATION') === false) {
            return null;
        }

        $lastLogins = $this->splitCsvValue($this->extractSubscriberListValue($raw, 'Last Logins'));

        $info = [
            'account_id' => $this->extractSubscriberListValue($raw, 'Google Account ID'),
            'name' => $this->extractSubscriberListValue($raw, 'Name'),
            'given_name' => $this->extractSubscriberListValue($raw, 'Given Name'),
            'family_name' => $this->extractSubscriberListValue($raw, 'Family Name'),
            'email' => $this->extractSubscriberListValue($raw, 'e-Mail'),
            'alternate_emails' => $this->splitCsvValue($this->extractSubscriberListValue($raw, 'Alternate e-Mails')),
            'created_on_utc' => $this->googleUtcToUtcDisplay($this->extractSubscriberListValue($raw, 'Created on')),
            'created_on_local' => $this->googleUtcToLocal($this->extractSubscriberListValue($raw, 'Created on')),
            'terms_of_service_ip' => $this->normalizeIp($this->extractSubscriberListValue($raw, 'Terms of Service IP')),
            'terms_of_service_language' => $this->extractSubscriberListValue($raw, 'Terms of Service Language'),
            'terms_of_service_country' => $this->extractSubscriberListValue($raw, 'Terms of Service Country'),
            'provider' => $this->extractSubscriberListValue($raw, 'Provider for Consumer Services'),
            'birthday' => $this->extractSubscriberListValue($raw, 'Birthday (Month Day, Year)'),
            'services' => $this->splitCsvValue($this->extractSubscriberListValue($raw, 'Services')),
            'deletion_date' => $this->extractSubscriberListValue($raw, 'Deletion Date'),
            'deletion_ip' => $this->normalizeIp($this->extractSubscriberListValue($raw, 'Deletion IP')),
            'end_of_service_date' => $this->extractSubscriberListValue($raw, 'End of Service Date'),
            'status' => $this->extractSubscriberListValue($raw, 'Status'),
            'last_updated_utc' => $this->googleUtcToUtcDisplay($this->extractSubscriberListValue($raw, 'Last Updated Date')),
            'last_updated_local' => $this->googleUtcToLocal($this->extractSubscriberListValue($raw, 'Last Updated Date')),
            'last_logins_utc' => array_values(array_filter(array_map(
                fn (string $value): ?string => $this->googleUtcToUtcDisplay($value),
                $lastLogins,
            ))),
            'last_logins_local' => array_values(array_filter(array_map(
                fn (string $value): ?string => $this->googleUtcToLocal($value),
                $lastLogins,
            ))),
            'contact_email' => $this->extractSubscriberListValue($raw, 'Contact e-Mail'),
            'recovery_email' => $this->extractSubscriberListValue($raw, 'Recovery e-Mail'),
            'recovery_sms' => $this->extractSubscriberListValue($raw, 'Recovery SMS'),
            'device_information' => stripos($text, 'No Devices') !== false ? 'No Devices' : null,
        ];

        return array_filter($info, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function extractSubscriberListValue(string $raw, string $label): ?string
    {
        if (! preg_match('/<li>\s*' . preg_quote($label, '/') . ':\s*(.*?)<\/li>/is', $raw, $m)) {
            return null;
        }

        $value = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $value !== '' ? $value : null;
    }

    private function splitCsvValue(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (string $item): string => trim($item),
            explode(',', $value),
        ), fn (string $item): bool => $item !== ''));
    }

    private function normalizeIp(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || ! filter_var($value, FILTER_VALIDATE_IP)) {
            return null;
        }

        return inet_ntop(inet_pton($value)) ?: $value;
    }

    private function googleUtcToLocal(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            $date = $this->parseDate($value, $this->extractTzLabel($value) ?? 'UTC');
            if (! $date) {
                return null;
            }

            return $date->copy()->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') . ' (GMT-3)';
        } catch (\Throwable) {
            return null;
        }
    }

    private function googleUtcToUtcDisplay(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            $date = $this->parseDate($value, $this->extractTzLabel($value) ?? 'UTC');
            if (! $date) {
                return $value;
            }

            return $date->copy()->setTimezone('UTC')->format('d/m/Y H:i:s') . ' (UTC)';
        } catch (\Throwable) {
            return $value;
        }
    }

    private function mergeIdentifiers(array $base, array $patterns, string $text): array
    {
        $out = [];

        foreach ($base as $identifier) {
            $type = (string) ($identifier['type'] ?? 'ID');
            $value = (string) ($identifier['value'] ?? '');
            if ($value !== '') {
                $out["{$type}:{$value}"] = ['type' => $type, 'value' => $value];
            }
        }

        foreach ($patterns as $type => $pattern) {
            if (! preg_match_all($pattern, $text, $matches)) {
                continue;
            }

            foreach (($matches[1] ?? $matches[0] ?? []) as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $out["{$type}:{$value}"] = ['type' => $type, 'value' => $value];
                }
            }
        }

        return array_values($out);
    }
}
