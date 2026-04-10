<?php

namespace App\Services\AnaliseInteligente;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use Illuminate\Support\Facades\Http;

class RunStepper
{
    public function step(AnaliseRun $run, int $chunk = 5, float $maxSeconds = 1.5): int
    {
        if ($run->status !== 'running') {
            return 0;
        }

        $start = microtime(true);

        $rows = AnaliseRunIp::query()
            ->where('analise_run_id', $run->id)
            ->where('enriched', false)
            ->orderBy('id')
            ->limit($chunk)
            ->get();

        if ($rows->isEmpty()) {
            $this->finalizeIfDone($run);
            return 0;
        }

        $processedThisStep = 0;

        foreach ($rows as $row) {
            if ((microtime(true) - $start) >= $maxSeconds) {
                break;
            }

            $ip = trim((string) $row->ip);

            if ($ip === '') {
                $row->enriched = true;
                $row->save();
                $processedThisStep++;
                continue;
            }

            if ($this->isPrivateOrReservedIp($ip)) {
                IpEnrichment::updateOrCreate(
                    ['ip' => $ip],
                    [
                        'status' => 'fail',
                        'message' => 'private_or_reserved_ip',
                        'city' => null,
                        'isp' => null,
                        'org' => null,
                        'mobile' => null,
                        'fetched_at' => now(),
                    ]
                );

                $row->enriched = true;
                $row->save();
                $processedThisStep++;
                continue;
            }

            $existing = IpEnrichment::where('ip', $ip)->first();

            // Só reaproveita se for recente + success + tiver provider preenchido
            if ($this->shouldReuseExistingEnrichment($existing)) {
                $row->enriched = true;
                $row->save();
                $processedThisStep++;
                continue;
            }

            try {
                $resp = Http::timeout(2)
                    ->connectTimeout(1)
                    ->retry(0, 0)
                    ->get("http://ip-api.com/json/{$ip}", [
                        'fields' => 'status,message,query,city,isp,org,mobile',
                    ]);

                $json = $resp->ok() ? $resp->json() : null;

                if (! is_array($json) || (($json['status'] ?? null) !== 'success')) {
                    IpEnrichment::updateOrCreate(
                        ['ip' => $ip],
                        [
                            'status' => 'fail',
                            'message' => is_array($json)
                                ? ($json['message'] ?? 'lookup_failed')
                                : ('http_' . $resp->status()),
                            'city' => $existing?->city,
                            'isp' => $existing?->isp,
                            'org' => $existing?->org,
                            'mobile' => $existing?->mobile,
                            'fetched_at' => now(),
                        ]
                    );
                } else {
                    $isp = $this->cleanNullableString($json['isp'] ?? null);
                    $org = $this->cleanNullableString($json['org'] ?? null);
                    $city = $this->cleanNullableString($json['city'] ?? null);

                    IpEnrichment::updateOrCreate(
                        ['ip' => $ip],
                        [
                            'status' => ($isp || $org || $city) ? 'success' : 'fail',
                            'message' => ($isp || $org || $city) ? null : 'empty_provider_response',
                            'city' => $city,
                            'isp' => $isp,
                            'org' => $org,
                            'mobile' => $json['mobile'] ?? false,
                            'fetched_at' => now(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                IpEnrichment::updateOrCreate(
                    ['ip' => $ip],
                    [
                        'status' => 'fail',
                        'message' => 'exception:' . mb_substr($e->getMessage(), 0, 180),
                        'city' => $existing?->city,
                        'isp' => $existing?->isp,
                        'org' => $existing?->org,
                        'mobile' => $existing?->mobile,
                        'fetched_at' => now(),
                    ]
                );
            }

            $row->enriched = true;
            $row->save();
            $processedThisStep++;
        }

        $doneCount = AnaliseRunIp::where('analise_run_id', $run->id)
            ->where('enriched', true)
            ->count();

        $run->processed_unique_ips = $doneCount;
        $run->progress = $run->total_unique_ips > 0
            ? (int) floor(($doneCount / $run->total_unique_ips) * 100)
            : 100;

        $this->finalizeIfDone($run);
        $run->save();

        return $processedThisStep;
    }

    private function finalizeIfDone(AnaliseRun $run): void
    {
        $pending = AnaliseRunIp::where('analise_run_id', $run->id)
            ->where('enriched', false)
            ->exists();

        if (! $pending) {
            $run->status = 'done';
            $run->progress = 100;
            $run->processed_unique_ips = $run->total_unique_ips;
        }
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    private function shouldReuseExistingEnrichment(?IpEnrichment $existing): bool
    {
        if (! $existing) {
            return false;
        }

        if (! $existing->fetched_at || ! $existing->fetched_at->gt(now()->subDays(30))) {
            return false;
        }

        if (($existing->status ?? null) !== 'success') {
            return false;
        }

        $isp = $this->cleanNullableString($existing->isp);
        $org = $this->cleanNullableString($existing->org);

        return filled($isp) || filled($org);
    }

    private function cleanNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
