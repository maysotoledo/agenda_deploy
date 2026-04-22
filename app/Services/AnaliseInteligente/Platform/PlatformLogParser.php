<?php

namespace App\Services\AnaliseInteligente\Platform;

use Carbon\Carbon;

class PlatformLogParser
{
    public function __construct(
        private readonly string $source,
        private readonly string $label,
    ) {}

    public function parse(string $rawText): array
    {
        $text = $this->normalizeText($rawText);

        $events = $this->deduplicateEvents(array_merge(
            $this->extractJsonEvents($text),
            $this->extractTextEvents($text),
        ));

        $times = array_values(array_filter(array_map(fn ($e) => $e['time_utc'] ?? null, $events)));
        usort($times, fn ($a, $b) => $a->timestamp <=> $b->timestamp);

        return [
            'source' => $this->source,
            'platform_label' => $this->label,
            'events' => $events,
            'emails' => $this->extractEmails($text),
            'phones' => $this->extractPhones($text),
            'identifiers' => $this->extractIdentifiers($text),
            'range_start_utc' => $times[0] ?? null,
            'range_end_utc' => $times[count($times) - 1] ?? null,
        ];
    }

    private function normalizeText(string $raw): string
    {
        if (preg_match('/<\s*(html|body|table|div|span|br)\b/i', $raw)) {
            $raw = html_entity_decode(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], "\n", $raw)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $raw = preg_replace("/[ \t]+/", ' ', $raw) ?? $raw;
        $raw = preg_replace("/\n{3,}/", "\n\n", $raw) ?? $raw;

        return trim($raw);
    }

    private function extractJsonEvents(string $text): array
    {
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            return [];
        }

        $events = [];
        $this->walkJson($decoded, $events);

        return $events;
    }

    private function walkJson(mixed $node, array &$events): void
    {
        if (! is_array($node)) {
            return;
        }

        if ($this->isAssoc($node)) {
            $flat = $this->flattenAssoc($node);
            $text = implode(' ', array_map(
                fn ($key, $value) => "{$key}: {$value}",
                array_keys($flat),
                array_values($flat),
            ));

            $event = $this->parseBlock($text);
            if ($event) {
                $events[] = $event;
            }
        }

        foreach ($node as $child) {
            $this->walkJson($child, $events);
        }
    }

    private function flattenAssoc(array $data, string $prefix = ''): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $out += $this->flattenAssoc($value, $path);
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $out[$path] = trim((string) $value);
            }
        }

        return $out;
    }

    private function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    private function extractTextEvents(string $text): array
    {
        $events = [];

        foreach ($this->buildBlocks($text) as $block) {
            $event = $this->parseBlock($block);
            if ($event) {
                $events[] = $event;
            }
        }

        return $events;
    }

    private function buildBlocks(string $text): array
    {
        $blocks = [];
        $current = [];
        $lines = preg_split("/\n+/", $text) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                if (! empty($current)) {
                    $blocks[] = implode("\n", $current);
                    $current = [];
                }
                continue;
            }

            if ($this->startsWithDateTime($line) && ! empty($current)) {
                $blocks[] = implode("\n", $current);
                $current = [];
            }

            $current[] = $line;

            if ($this->containsIp($line) && $this->extractDateFromText($line)['date'] instanceof Carbon) {
                $blocks[] = $line;
            }
        }

        if (! empty($current)) {
            $blocks[] = implode("\n", $current);
        }

        return array_values(array_unique($blocks));
    }

    private function startsWithDateTime(string $line): bool
    {
        return (bool) preg_match('/^\s*(?:\d{4}-\d{2}-\d{2}[T\s]|\d{1,2}\/\d{1,2}\/\d{4}\s+)/', $line);
    }

    private function parseBlock(string $block): ?array
    {
        $ip = $this->extractBestIpFromBlock($block);
        if (! $ip) {
            return null;
        }

        $date = $this->extractDateFromText($block);
        $timeUtc = $date['date'] ?? null;
        if (! $timeUtc instanceof Carbon) {
            return null;
        }

        $description = $this->cleanDescription($block, $ip);

        return [
            'time_utc' => $timeUtc,
            'tz_label' => $date['tz_label'] ?? 'UTC',
            'ip' => $ip,
            'logical_port' => $this->extractLogicalPort($block, $ip),
            'action' => $this->guessAction($description),
            'description' => $description,
        ];
    }

    private function extractDateFromText(string $text): array
    {
        $patterns = [
            '/\b\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?\b/',
            '/\b\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}\s*(?:UTC|GMT[+-]?\d+|[+-]\d{2}:?\d{2})?\b/i',
            '/\b\d{1,2}\/\d{1,2}\/\d{4}\s+\d{2}:\d{2}:\d{2}\s*(?:UTC|GMT[+-]?\d+|[+-]\d{2}:?\d{2})?\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $text, $match)) {
                continue;
            }

            $raw = trim($match[0]);
            $tzLabel = $this->extractTzLabel($raw) ?? $this->extractTzLabel($text) ?? 'UTC';
            $date = $this->parseDate($raw, $tzLabel);

            if ($date) {
                return ['date' => $date, 'tz_label' => $tzLabel];
            }
        }

        return ['date' => null, 'tz_label' => null];
    }

    private function parseDate(string $raw, string $tzLabel): ?Carbon
    {
        $timezone = $this->mapTzLabelToTimezone($tzLabel);
        $normalized = trim(preg_replace('/\s*(UTC|GMT[+-]?\d+|[+-]\d{2}:?\d{2})$/i', '', $raw) ?? $raw);

        try {
            if (str_contains($raw, 'T')) {
                return Carbon::parse($raw, 'UTC')->setTimezone('UTC');
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $normalized)) {
                return Carbon::createFromFormat('Y-m-d H:i:s', $normalized, $timezone)->setTimezone('UTC');
            }

            if (preg_match('/^(?<a>\d{1,2})\/(?<b>\d{1,2})\/(?<year>\d{4})\s+(?<time>\d{2}:\d{2}:\d{2})$/', $normalized, $m)) {
                $first = (int) $m['a'];
                $second = (int) $m['b'];
                $format = $first > 12 ? 'd/m/Y H:i:s' : ($second > 12 ? 'm/d/Y H:i:s' : 'd/m/Y H:i:s');

                return Carbon::createFromFormat($format, $normalized, $timezone)->setTimezone('UTC');
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function extractTzLabel(string $text): ?string
    {
        if (preg_match('/\b(UTC|GMT[+-]?\d+|GMT|[+-]\d{2}:?\d{2}|Z)\b/i', $text, $m)) {
            return strtoupper($m[1]) === 'Z' ? 'UTC' : strtoupper($m[1]);
        }

        return null;
    }

    private function mapTzLabelToTimezone(?string $tzLabel): string
    {
        $tzLabel = strtoupper(trim((string) $tzLabel));

        if ($tzLabel === '' || in_array($tzLabel, ['UTC', 'GMT'], true)) {
            return 'UTC';
        }

        if (preg_match('/^GMT([+-]?\d+)$/', $tzLabel, $m)) {
            return sprintf('%+03d:00', (int) $m[1]);
        }

        if (preg_match('/^([+-]\d{2}):?(\d{2})$/', $tzLabel, $m)) {
            return "{$m[1]}:{$m[2]}";
        }

        return 'UTC';
    }

    private function containsIp(string $text): bool
    {
        return $this->extractBestIpFromBlock($text) !== null;
    }

    private function extractBestIpFromBlock(string $block): ?string
    {
        $candidates = [];

        if (preg_match_all('/\b(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)\b/', $block, $m4, PREG_OFFSET_CAPTURE)) {
            foreach ($m4[0] as [$ip, $offset]) {
                if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || $this->shouldIgnoreIpv4($ip, $block, $offset)) {
                    continue;
                }

                $candidates[] = ['ip' => $ip, 'offset' => $offset];
            }
        }

        if (preg_match_all('/\b[0-9a-f:]{2,}\b/i', $block, $m6, PREG_OFFSET_CAPTURE)) {
            foreach ($m6[0] as [$ip, $offset]) {
                if (! str_contains($ip, ':') || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    continue;
                }

                $candidates[] = ['ip' => $ip, 'offset' => $offset];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $a['offset'] <=> $b['offset']);

        return $candidates[count($candidates) - 1]['ip'];
    }

    private function shouldIgnoreIpv4(string $ip, string $block, int $offset): bool
    {
        if ($ip === '0.0.0.0' || str_starts_with($ip, '127.') || preg_match('/^\d{1,3}\.0\.0\.0$/', $ip)) {
            $around = substr($block, max(0, $offset - 30), 70);

            return stripos($around, 'chrome/') !== false
                || stripos($around, 'firefox/') !== false
                || stripos($around, 'safari/') !== false
                || stripos($around, 'version/') !== false
                || $ip === '0.0.0.0'
                || str_starts_with($ip, '127.');
        }

        return false;
    }

    private function extractLogicalPort(string $text, string $ip): ?int
    {
        if (preg_match('/\b' . preg_quote($ip, '/') . '\b\s*[:;,]?\s*(?:port|porta)?\s*(\d{1,5})\b/i', $text, $m)) {
            $port = (int) $m[1];
            return $port > 0 && $port <= 65535 ? $port : null;
        }

        return null;
    }

    private function cleanDescription(string $block, string $ip): string
    {
        $description = str_replace($ip, '', $block);
        $description = preg_replace('/\s+/', ' ', $description) ?? $description;

        return trim($description) !== '' ? trim($description) : 'Evento de acesso';
    }

    private function guessAction(string $description): ?string
    {
        $text = mb_strtolower($description);

        foreach ([
            'login' => 'Login',
            'sign in' => 'Login',
            'signin' => 'Login',
            'log in' => 'Login',
            'authentication' => 'Autenticação',
            'auth' => 'Autenticação',
            'password' => 'Senha',
            'senha' => 'Senha',
            'recovery' => 'Recuperação',
            'recuperação' => 'Recuperação',
            'device' => 'Dispositivo',
            'dispositivo' => 'Dispositivo',
            'icloud' => 'iCloud',
            'gmail' => 'Gmail',
            'youtube' => 'YouTube',
            'maps' => 'Maps',
            'location' => 'Localização',
            'localização' => 'Localização',
            'purchase' => 'Compra',
            'payment' => 'Pagamento',
        ] as $needle => $label) {
            if (str_contains($text, $needle)) {
                return $label;
            }
        }

        return null;
    }

    private function extractEmails(string $text): array
    {
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $matches);
        $emails = array_values(array_unique(array_map('strtolower', $matches[0] ?? [])));
        sort($emails);

        return $emails;
    }

    private function extractPhones(string $text): array
    {
        preg_match_all('/(?<!\d)(?:\+?\d[\d\s().-]{8,}\d)(?!\d)/', $text, $matches);
        $phones = [];

        foreach ($matches[0] ?? [] as $phone) {
            $digits = preg_replace('/\D+/', '', $phone);
            if (strlen((string) $digits) >= 10 && strlen((string) $digits) <= 15) {
                $phones[$digits] = trim($phone);
            }
        }

        return array_values($phones);
    }

    private function extractIdentifiers(string $text): array
    {
        $patterns = [
            'IMEI' => '/\b\d{15}\b/',
            'DSID' => '/\b(?:DSID|dsid)\s*[:#-]?\s*([A-Z0-9._-]{5,})/i',
            'Serial' => '/\b(?:serial|n[uú]mero de s[eé]rie|cssn)\s*[:#-]?\s*([A-Z0-9._-]{5,})/i',
            'Android ID' => '/\b(?:android id|android_id)\s*[:#-]?\s*([a-f0-9]{8,32})/i',
            'GAIA' => '/\b(?:gaia|google id)\s*[:#-]?\s*([A-Z0-9._-]{5,})/i',
        ];

        $out = [];

        foreach ($patterns as $type => $pattern) {
            if (! preg_match_all($pattern, $text, $matches)) {
                continue;
            }

            $values = $matches[1] ?? $matches[0] ?? [];
            foreach ($values as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $out["{$type}:{$value}"] = ['type' => $type, 'value' => $value];
                }
            }
        }

        return array_values($out);
    }

    private function deduplicateEvents(array $events): array
    {
        $out = [];

        foreach ($events as $event) {
            $time = $event['time_utc'] instanceof Carbon ? $event['time_utc']->timestamp : (string) ($event['time_utc'] ?? '');
            $key = md5($time . '|' . ($event['ip'] ?? '') . '|' . mb_substr((string) ($event['description'] ?? ''), 0, 160));
            $out[$key] = $event;
        }

        return array_values($out);
    }
}
