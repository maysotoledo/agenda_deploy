<?php

namespace App\Services\AnaliseInteligente\Generic;

use Carbon\Carbon;

class GenericLogParser
{
    public function parse(string $rawText): array
    {
        $text = $this->normalizeText($rawText);

        $events = $this->extractEventsByBlocks($text);
        $emails = $this->extractEmails($text);

        $times = array_values(array_filter(array_map(fn ($e) => $e['time_utc'] ?? null, $events)));
        usort($times, fn ($a, $b) => $a->timestamp <=> $b->timestamp);

        return [
            'events' => $events,
            'emails' => $emails,
            'range_start_utc' => $times[0] ?? null,
            'range_end_utc' => $times[count($times) - 1] ?? null,
        ];
    }

    private function normalizeText(string $raw): string
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $raw = preg_replace("/[ \t]+/", ' ', $raw) ?? $raw;
        $raw = preg_replace("/\n{3,}/", "\n\n", $raw) ?? $raw;
        return trim($raw);
    }

    private function extractEventsByBlocks(string $text): array
    {
        $lines = preg_split("/\n+/", $text) ?: [];

        $blocks = [];
        $current = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if ($this->startsWithDateTime($line)) {
                if (! empty($current)) {
                    $blocks[] = implode("\n", $current);
                    $current = [];
                }
            }

            $current[] = $line;
        }

        if (! empty($current)) {
            $blocks[] = implode("\n", $current);
        }

        $events = [];
        foreach ($blocks as $block) {
            $event = $this->parseBlock($block);
            if ($event) {
                $events[] = $event;
            }
        }

        return $events;
    }

    private function startsWithDateTime(string $line): bool
    {
        return (bool) preg_match('/^\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2}\b/', $line);
    }

    private function parseBlock(string $block): ?array
    {
        if (! preg_match('/^(?<date>\d{2}\/\d{2}\/\d{4})\s+(?<time>\d{2}:\d{2}:\d{2})\b/', $block, $m)) {
            return null;
        }

        $date = $m['date'];
        $time = $m['time'];

        $tzLabel = $this->extractTzLabel($block);

        $dtUtc = $this->toUtcFromDateTime($date, $time, $tzLabel);
        if (! $dtUtc) {
            return null;
        }

        // ✅ IP REAL: pega o último IP válido do bloco (IPv4/IPv6), ignorando “Chrome/126.0.0.0” etc
        $ip = $this->extractBestIpFromBlock($block);
        if (! $ip) {
            return null;
        }

        $logicalPort = $this->extractLogicalPort($block, $ip);

        $desc = $block;
        $desc = preg_replace('/^\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2}\s*/', '', $desc) ?? $desc;
        $desc = str_ireplace('dd/mm/YYYY HH:mm:ss', '', $desc);

        if ($tzLabel) {
            $desc = str_ireplace($tzLabel, '', $desc);
        }

        $desc = str_replace($ip, '', $desc);

        if ($logicalPort !== null) {
            $desc = preg_replace('/\b' . preg_quote((string) $logicalPort, '/') . '\b/', '', $desc) ?? $desc;
        }

        $desc = trim(preg_replace('/\s+/', ' ', $desc) ?? '');

        return [
            'time_utc' => $dtUtc,
            'tz_label' => $tzLabel ?? 'UTC',
            'ip' => $ip,
            'logical_port' => $logicalPort,
            'action' => $desc !== '' ? $this->guessAction($desc) : null,
            'description' => $desc !== '' ? $desc : null,
        ];
    }

    private function extractTzLabel(string $text): ?string
    {
        if (preg_match('/\b(UTC|GMT0|GMT[+-]?\d+|[+-]\d{2}:\d{2})\b/i', $text, $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    /**
     * ✅ Extrai todos os IPs do bloco, remove falsos positivos (Chrome/xxx.0.0.0),
     * ignora IPs específicos e pega o ÚLTIMO IP válido (coluna “IP” do log).
     */
    private function extractBestIpFromBlock(string $block): ?string
    {
        $candidates = [];

        // IPv4 com offsets
        if (preg_match_all(
            '/\b(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)\b/',
            $block,
            $m4,
            PREG_OFFSET_CAPTURE
        )) {
            foreach ($m4[0] as [$ip, $offset]) {
                if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    continue;
                }

                if ($this->shouldIgnoreIpv4($ip, $block, $offset)) {
                    continue;
                }

                $candidates[] = ['ip' => $ip, 'offset' => $offset];
            }
        }

        // IPv6 com offsets (candidatos) + validação final
        if (preg_match_all('/\b[0-9a-f:]{2,}\b/i', $block, $m6, PREG_OFFSET_CAPTURE)) {
            foreach ($m6[0] as [$cand, $offset]) {
                if (! str_contains($cand, ':')) {
                    continue;
                }

                if (! filter_var($cand, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    continue;
                }

                $candidates[] = ['ip' => $cand, 'offset' => $offset];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // pega o último (maior offset) → tende a ser o IP real da coluna “IP”
        usort($candidates, fn ($a, $b) => $a['offset'] <=> $b['offset']);

        return $candidates[count($candidates) - 1]['ip'];
    }

    /**
     * Ignora:
     * - IPs que você pediu explicitamente
     * - versões do Chrome/UA (ex.: Chrome/126.0.0.0, Chrome/127.0.0.0, Chrome/128.0.0.0)
     * - loopback clássico (127.0.0.0/8) e 0.0.0.0
     */
    private function shouldIgnoreIpv4(string $ip, string $block, int $offset): bool
    {
        // pedidos explícitos
        if (in_array($ip, ['127.0.0.0', '126.0.0.0', '128.0.0.0', '0.0.0.0'], true)) {
            return true;
        }

        // loopback
        if (str_starts_with($ip, '127.')) {
            return true;
        }

        // falsos positivos de User-Agent: "Chrome/117.0.0.0", "Chrome/126.0.0.0", etc
        // checa contexto ao redor do match (antes e depois)
        $start = max(0, $offset - 20);
        $ctx = substr($block, $start, 40);

        if (preg_match('/(Chrome|Chromium|Firefox|Edg|OPR|Safari)\/\s*$/i', substr($ctx, 0, 25))) {
            return true;
        }

        // forma ainda mais direta: se logo antes tem "Chrome/"
        $before = substr($block, max(0, $offset - 10), 10);
        if (preg_match('/Chrome\/$/i', $before)) {
            return true;
        }

        // padrão de UA muito comum: N.0.0.0 (versão)
        if (preg_match('/^\d{1,3}\.0\.0\.0$/', $ip)) {
            // se no bloco há "Chrome/" e esse ip aparece perto, é versão, não IP de acesso
            $around = substr($block, max(0, $offset - 30), 60);
            if (stripos($around, 'chrome/') !== false || stripos($around, 'firefox/') !== false || stripos($around, 'edg/') !== false || stripos($around, 'opr/') !== false) {
                return true;
            }
        }

        return false;
    }

    private function extractLogicalPort(string $text, string $ip): ?int
    {
        $re = '/\b' . preg_quote($ip, '/') . '\b\s+(\d{1,5})\b/';
        if (preg_match($re, $text, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/\b(\d{1,5})\b\s*$/', trim($text), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function extractEmails(string $text): array
    {
        preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m);
        $emails = [];

        foreach ($m[0] ?? [] as $match) {
            $email = $this->sanitizeEmailCandidate($match);

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $emails[] = strtolower($email);
        }

        $emails = array_values(array_unique($emails));
        sort($emails);
        return $emails;
    }

    private function sanitizeEmailCandidate(string $email): string
    {
        $cleaned = preg_replace('/(?:UTC|GMT0|GMT[+-]?\d+|[+-]\d{2}:\d{2})$/i', '', trim($email));

        return $cleaned !== null ? rtrim($cleaned, ".,;: \t\n\r\0\x0B") : trim($email);
    }

    private function guessAction(string $description): ?string
    {
        $d = mb_strtolower($description);

        foreach ([
            'criação' => 'Criação',
            'criar' => 'Criação',
            'login' => 'Login',
            'logout' => 'Logout',
            'sessão' => 'Sessão',
            'session' => 'Sessão',
            'token' => 'Token',
            'upload de foto' => 'Upload',
            'upload de vídeo' => 'Upload',
            'upload' => 'Upload',
            'download' => 'Download',
            'alteração' => 'Alteração',
            'update' => 'Alteração',
            'desativada' => 'Token',
        ] as $needle => $label) {
            if (str_contains($d, $needle)) return $label;
        }

        return null;
    }

    private function toUtcFromDateTime(string $date, string $time, ?string $tzLabel): ?Carbon
    {
        $tz = $this->mapTzLabelToTimezone($tzLabel);

        try {
            $dt = Carbon::createFromFormat('d/m/Y H:i:s', "{$date} {$time}", $tz);
            return $dt->copy()->setTimezone('UTC');
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapTzLabelToTimezone(?string $tzLabel): string
    {
        $tzLabel = trim((string) $tzLabel);

        if ($tzLabel === '' || strtoupper($tzLabel) === 'UTC' || strtoupper($tzLabel) === 'GMT0' || strtoupper($tzLabel) === 'GMT') {
            return 'UTC';
        }

        if (preg_match('/^GMT([+-]?\d+)$/i', $tzLabel, $m)) {
            $h = (int) $m[1];
            return sprintf('%+03d:00', $h);
        }

        if (preg_match('/^[+-]\d{2}:\d{2}$/', $tzLabel)) {
            return $tzLabel;
        }

        return 'UTC';
    }
}
