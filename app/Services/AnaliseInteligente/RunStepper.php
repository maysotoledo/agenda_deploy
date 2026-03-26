<?php

namespace App\Services\AnaliseInteligente;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use Illuminate\Support\Facades\Http;

class RunStepper
{
    public function step(AnaliseRun $run, int $chunk = 15): int
    {
        if ($run->status !== 'running') {
            return 0;
        }

        $rows = AnaliseRunIp::query()
            ->where('analise_run_id', $run->id)
            ->where('enriched', false)
            ->orderBy('id')
            ->limit($chunk)
            ->get();

        if ($rows->isEmpty()) {
            $pending = AnaliseRunIp::where('analise_run_id', $run->id)->where('enriched', false)->exists();
            if (! $pending) {
                $run->status = 'done';
                $run->progress = 100;
                $run->processed_unique_ips = $run->total_unique_ips;
                $run->save();
            }
            return 0;
        }

        $processedThisStep = 0;

        foreach ($rows as $row) {
            $ip = $row->ip;

            // IP privado/reservado => grava fail sem chamar ip-api
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

                if (IpEnrichment::where('ip', $ip)->exists()) {
                    $row->enriched = true;
                    $row->save();
                    $processedThisStep++;
                }

                continue;
            }

            // Reaproveita enrichment global se for recente
            $existing = IpEnrichment::where('ip', $ip)->first();
            if ($existing && $existing->fetched_at && $existing->fetched_at->gt(now()->subDays(30))) {
                $row->enriched = true;
                $row->save();
                $processedThisStep++;
                continue;
            }

            // Busca na ip-api e grava SEMPRE (success ou fail)
            try {
                $resp = Http::timeout(3)
                    ->retry(1, 200)
                    ->get("http://ip-api.com/json/{$ip}", [
                        'fields' => 'status,message,query,city,isp,org,mobile',
                    ]);

                $j = $resp->ok() ? $resp->json() : null;

                if (! is_array($j) || (($j['status'] ?? null) !== 'success')) {
                    IpEnrichment::updateOrCreate(
                        ['ip' => $ip],
                        [
                            'status' => 'fail',
                            'message' => is_array($j) ? ($j['message'] ?? null) : ('http_' . $resp->status()),
                            'city' => null,
                            'isp' => null,
                            'org' => null,
                            'mobile' => null,
                            'fetched_at' => now(),
                        ]
                    );
                } else {
                    IpEnrichment::updateOrCreate(
                        ['ip' => $ip],
                        [
                            'status' => 'success',
                            'message' => null,
                            'city' => $j['city'] ?? null,
                            'isp' => $j['isp'] ?? null,
                            'org' => $j['org'] ?? null,
                            'mobile' => $j['mobile'] ?? null,
                            'fetched_at' => now(),
                        ]
                    );
                }
            } catch (\Throwable $e) {
                IpEnrichment::updateOrCreate(
                    ['ip' => $ip],
                    [
                        'status' => 'fail',
                        'message' => $e->getMessage(),
                        'city' => null,
                        'isp' => null,
                        'org' => null,
                        'mobile' => null,
                        'fetched_at' => now(),
                    ]
                );
            }

            // ✅ só marca enriched se existir enrichment
            if (IpEnrichment::where('ip', $ip)->exists()) {
                $row->enriched = true;
                $row->save();
                $processedThisStep++;
            }
        }

        $doneCount = AnaliseRunIp::where('analise_run_id', $run->id)->where('enriched', true)->count();
        $run->processed_unique_ips = $doneCount;

        $run->progress = $run->total_unique_ips > 0
            ? (int) floor(($doneCount / $run->total_unique_ips) * 100)
            : 100;

        $pending = AnaliseRunIp::where('analise_run_id', $run->id)->where('enriched', false)->exists();
        if (! $pending) {
            $run->status = 'done';
            $run->progress = 100;
            $run->processed_unique_ips = $run->total_unique_ips;
        }

        $run->save();

        return $processedThisStep;
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
