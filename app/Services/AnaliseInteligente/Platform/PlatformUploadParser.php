<?php

namespace App\Services\AnaliseInteligente\Platform;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class PlatformUploadParser
{
    public function __construct(
        private readonly string $source,
        private readonly string $label,
        private readonly PlatformLogParser $parser,
    ) {}

    public function parseStoredUploads(array $storedPaths): array
    {
        return $this->parseUploadFragmentsByTarget($this->extractTextFragmentsFromUploads($storedPaths));
    }

    public function extractTextFragmentsFromUploads(array $storedPaths): array
    {
        $fragments = [];
        $disk = Storage::disk('public');

        foreach ($storedPaths as $storedPath) {
            if (! is_string($storedPath) || ! $disk->exists($storedPath)) {
                continue;
            }

            $fullPath = $disk->path($storedPath);
            foreach ($this->extractTextFragmentsFromFile($fullPath, $storedPath) as $fragment) {
                $text = $this->toValidUtf8((string) ($fragment['text'] ?? ''), 3_000_000);

                if (trim($text) !== '') {
                    $fragments[] = [
                        'name' => (string) ($fragment['name'] ?? $storedPath),
                        'text' => $text,
                    ];
                }
            }
        }

        return $fragments;
    }

    public function buildIpsMap(array $events): array
    {
        $ipsMap = [];

        foreach ($events as $event) {
            $ip = trim((string) ($event['ip'] ?? ''));
            if ($ip === '') {
                continue;
            }

            $time = $event['time_utc'] ?? null;
            $ts = null;

            if ($time instanceof Carbon) {
                $ts = $time->timestamp;
            } elseif (is_string($time) && trim($time) !== '') {
                $ts = strtotime($time) ?: null;
            } elseif (is_int($time)) {
                $ts = $time;
            }

            $ipsMap[$ip] ??= ['occurrences' => 0, 'last_seen_ts' => $ts];
            $ipsMap[$ip]['occurrences']++;

            if ($ts && ($ipsMap[$ip]['last_seen_ts'] === null || $ts > $ipsMap[$ip]['last_seen_ts'])) {
                $ipsMap[$ip]['last_seen_ts'] = $ts;
            }
        }

        return $ipsMap;
    }

    public function resolveTarget(array $parsed): ?string
    {
        $emails = (array) ($parsed['emails'] ?? []);
        if (count($emails) > 0) {
            return (string) $emails[0];
        }

        $identifiers = (array) ($parsed['identifiers'] ?? []);
        $first = $identifiers[0]['value'] ?? null;

        return is_string($first) && trim($first) !== '' ? trim($first) : null;
    }

    private function parseUploadFragmentsByTarget(array $fragments): array
    {
        $groups = [];

        foreach ($fragments as $fragment) {
            $text = (string) ($fragment['text'] ?? '');
            if (trim($text) === '') {
                continue;
            }

            $name = (string) ($fragment['name'] ?? 'arquivo');
            $storedPath = $this->storedPathFromFragmentName($name);
            $parsed = $this->parser->parse($text);
            $parsed['_fragment_name'] = $name;

            $target = $this->resolveStoredPathTarget($storedPath)
                ?: $this->resolveFragmentTarget($parsed, $name);
            $key = $this->normalizeTargetKey($target);

            if (! $key) {
                $key = 'sem-alvo:' . md5($name);
            }

            $groups[$key] ??= [
                'resolved_target' => $target,
                'parsed_list' => [],
                'files' => [],
                'fragments' => [],
            ];

            if (
                (! is_string($groups[$key]['resolved_target'] ?? null) || trim((string) $groups[$key]['resolved_target']) === '')
                && is_string($target)
                && trim($target) !== ''
            ) {
                $groups[$key]['resolved_target'] = $target;
            }

            $groups[$key]['parsed_list'][] = $parsed;

            if ($storedPath !== '') {
                $groups[$key]['files'][$storedPath] = $storedPath;
            }

            $groups[$key]['fragments'][$name] = $name;
        }

        $out = [];
        foreach ($groups as $key => $group) {
            $merged = $this->mergeParsedFragments((array) ($group['parsed_list'] ?? []));

            $out[] = [
                'target_key' => $key,
                'target' => $this->resolveTarget($merged) ?: (is_string($group['resolved_target'] ?? null) ? trim((string) $group['resolved_target']) : null),
                'parsed' => $merged,
                'files' => array_values((array) ($group['files'] ?? [])),
                'fragments' => array_values((array) ($group['fragments'] ?? [])),
            ];
        }

        return $out;
    }

    private function extractTextFragmentsFromFile(string $absPath, string $storedPath): array
    {
        $ext = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));

        if ($ext === 'zip') {
            return $this->extractTextFragmentsFromZip($absPath, $storedPath);
        }

        if ($ext === 'pdf') {
            try {
                if (class_exists(\Smalot\PdfParser\Parser::class)) {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($absPath);

                    return [[
                        'name' => $storedPath,
                        'text' => (string) $pdf->getText(),
                    ]];
                }
            } catch (\Throwable) {
                return [];
            }

            return [];
        }

        return is_file($absPath)
            ? [[
                'name' => $storedPath,
                'text' => (string) file_get_contents($absPath),
            ]]
            : [];
    }

    private function extractTextFragmentsFromZip(string $absPath, string $storedPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($absPath) !== true) {
            return [];
        }

        $fragments = [];
        $allowed = ['txt', 'log', 'csv', 'json', 'html', 'htm', 'xml'];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || $this->shouldSkipZipEntry($name)) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowed, true)) {
                continue;
            }

            $content = $zip->getFromIndex($i);
            if (is_string($content) && trim($content) !== '') {
                $fragments[] = [
                    'name' => "{$storedPath}::{$name}",
                    'text' => $content,
                ];
            }
        }

        $zip->close();

        return $fragments;
    }

    private function shouldSkipZipEntry(string $name): bool
    {
        $lower = strtolower(str_replace('\\', '/', $name));

        if ($this->source === 'google') {
            return str_contains($lower, '/deletion markers/')
                || str_contains($lower, '/settings/')
                || str_ends_with($lower, '.mp3');
        }

        return false;
    }

    private function resolveFragmentTarget(array $parsed, string $fragmentName): ?string
    {
        if ($this->source === 'google') {
            if (preg_match('/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})(?=\.\d+\.(?:MyActivity|GoogleAccount)\.)/i', $fragmentName, $match)) {
                return strtolower(trim($match[1]));
            }

            if (preg_match('/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})(?=\.zip(?:::|$))/i', $fragmentName, $match)) {
                return strtolower(trim($match[1]));
            }
        }

        $subscriberEmail = data_get($parsed, 'google_subscriber_info.email');
        if (is_string($subscriberEmail) && trim($subscriberEmail) !== '') {
            return trim($subscriberEmail);
        }

        $subscriberAccount = data_get($parsed, 'google_subscriber_info.account_id');
        if (is_string($subscriberAccount) && trim($subscriberAccount) !== '') {
            return trim($subscriberAccount);
        }

        foreach ((array) ($parsed['emails'] ?? []) as $email) {
            $email = trim((string) $email);
            if ($email !== '') {
                return $email;
            }
        }

        foreach ((array) ($parsed['identifiers'] ?? []) as $identifier) {
            $value = is_array($identifier) ? trim((string) ($identifier['value'] ?? '')) : '';
            if ($value !== '') {
                return $value;
            }
        }

        if (preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $fragmentName, $matches)) {
            foreach ((array) ($matches[0] ?? []) as $candidate) {
                $candidate = strtolower(trim((string) $candidate));

                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                    return $candidate;
                }
            }
        }

        return $this->resolveTarget($parsed);
    }

    private function normalizeTargetKey(?string $target): ?string
    {
        $target = trim((string) $target);
        if ($target === '') {
            return null;
        }

        if (filter_var($target, FILTER_VALIDATE_EMAIL)) {
            return strtolower($target);
        }

        $normalized = mb_strtolower($target);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized) !== '' ? trim($normalized) : null;
    }

    private function storedPathFromFragmentName(string $name): string
    {
        $parts = explode('::', $name, 2);

        return trim((string) ($parts[0] ?? $name));
    }

    private function resolveStoredPathTarget(string $storedPath): ?string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return null;
        }

        if ($this->source === 'google') {
            if (preg_match('/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})(?=\.\d+\.(?:MyActivity|GoogleAccount)\.)/i', $storedPath, $match)) {
                return strtolower(trim($match[1]));
            }

            if (preg_match('/([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})(?=\.zip$)/i', $storedPath, $match)) {
                return strtolower(trim($match[1]));
            }
        }

        return null;
    }

    private function mergeParsedFragments(array $parsedList): array
    {
        $events = [];
        $emails = [];
        $phones = [];
        $identifiers = [];
        $extraData = [];

        foreach ($parsedList as $parsed) {
            foreach ($this->extraParsedKeys($parsed) as $key) {
                $value = $parsed[$key] ?? null;

                if (is_array($value)) {
                    $extraData[$key] = $this->mergeExtraParsedArray(
                        is_array($extraData[$key] ?? null) ? $extraData[$key] : null,
                        $value,
                    );
                } elseif ($value !== null && $value !== '') {
                    $extraData[$key] ??= $value;
                }
            }

            foreach (($parsed['events'] ?? []) as $event) {
                $time = $event['time_utc'] ?? null;
                $timestamp = $time instanceof Carbon ? $time->timestamp : (string) $time;
                $key = md5($timestamp . '|' . ($event['ip'] ?? '') . '|' . mb_substr((string) ($event['description'] ?? ''), 0, 160));
                $events[$key] = $event;
            }

            foreach ((array) ($parsed['emails'] ?? []) as $email) {
                $email = strtolower(trim((string) $email));
                if ($email !== '') {
                    $emails[$email] = $email;
                }
            }

            foreach ((array) ($parsed['phones'] ?? []) as $phone) {
                $phone = trim((string) $phone);
                if ($phone !== '') {
                    $phones[preg_replace('/\D+/', '', $phone) ?: $phone] = $phone;
                }
            }

            foreach ((array) ($parsed['identifiers'] ?? []) as $identifier) {
                if (! is_array($identifier)) {
                    continue;
                }

                $type = trim((string) ($identifier['type'] ?? 'ID'));
                $value = trim((string) ($identifier['value'] ?? ''));
                if ($value !== '') {
                    $identifiers["{$type}:{$value}"] = [
                        'type' => $type,
                        'value' => $value,
                    ];
                }
            }
        }

        $events = array_values($events);
        usort($events, function (array $a, array $b): int {
            $aTime = $a['time_utc'] ?? null;
            $bTime = $b['time_utc'] ?? null;

            return ($aTime instanceof Carbon ? $aTime->timestamp : 0)
                <=> ($bTime instanceof Carbon ? $bTime->timestamp : 0);
        });

        $times = array_values(array_filter(array_map(fn ($e) => $e['time_utc'] ?? null, $events)));

        $merged = [
            'source' => $this->source,
            'platform_label' => $this->label,
            'events' => $events,
            'emails' => array_values($emails),
            'phones' => array_values($phones),
            'identifiers' => array_values($identifiers),
            'range_start_utc' => $times[0] ?? null,
            'range_end_utc' => count($times) > 0 ? $times[count($times) - 1] : null,
        ];

        foreach ($extraData as $key => $value) {
            if ($value !== null && $value !== '' && $value !== []) {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    private function extraParsedKeys(array $parsed): array
    {
        return array_values(array_diff(array_keys($parsed), [
            'source',
            'platform_label',
            'events',
            'emails',
            'phones',
            'identifiers',
            'range_start_utc',
            'range_end_utc',
            '_fragment_name',
        ]));
    }

    private function mergeExtraParsedArray(?array $current, array $incoming): array
    {
        if (! $current) {
            return $incoming;
        }

        if ($this->isListArray($current) || $this->isListArray($incoming)) {
            $rows = array_merge($this->isListArray($current) ? $current : [$current], $this->isListArray($incoming) ? $incoming : [$incoming]);
            $merged = [];

            foreach ($rows as $row) {
                $key = is_array($row)
                    ? md5(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: serialize($row))
                    : md5((string) $row);

                $merged[$key] = $row;
            }

            return array_values($merged);
        }

        foreach ($incoming as $key => $value) {
            if (is_array($value)) {
                $existing = is_array($current[$key] ?? null) ? $current[$key] : [];
                $current[$key] = array_values(array_unique(array_merge($existing, $value)));
                continue;
            }

            if (! isset($current[$key]) || $current[$key] === '' || $current[$key] === null) {
                $current[$key] = $value;
            }
        }

        return $current;
    }

    private function isListArray(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    private function toValidUtf8(string $value, int $maxLen): string
    {
        $fixed = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if ($fixed === false) {
            $fixed = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value) ?? $value;
        }

        if ($maxLen > 0 && strlen($fixed) > $maxLen) {
            $fixed = substr($fixed, 0, $maxLen);
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $fixed) ?? $fixed;
    }
}
