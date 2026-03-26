<?php

namespace App\Services\AnaliseInteligente;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use Illuminate\Support\Facades\Http;

class RunStepper
{
    /**
     * Processa um lote de IPs por "tick" (poll),
     * respeitando um budget de tempo para não estourar 30s.
     */
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
            // ⏱️ respeita budget de tempo por tick
            if ((microtime(true) - $start) >= $maxSeconds) {
                break;
            }

            $ip = $row->ip;

            // IP privado/reservado => grava fail e marca enriched
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

            // Reaproveita enrichment recente (30 dias)
            $existing = IpEnrichment::where('ip', $ip)->first();
            if ($existing && $existing->fetched_at && $existing->fetched_at->gt(now()->subDays(30))) {
                $row->enriched = true;
                $row->save();
                $processedThisStep++;
                continue;
            }

            // HTTP: timeout curto para não travar o request do Livewire
            try {
                $resp = Http::timeout(2)   // ✅ curto
                    ->connectTimeout(1)    // ✅ curto
                    ->retry(0, 0)          // ✅ sem retry (não estourar tempo)
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
                // Sempre grava fail (para não ficar pendente infinito)
                IpEnrichment::updateOrCreate(
                    ['ip' => $ip],
                    [
                        'status' => 'fail',
                        'message' => 'exception:' . mb_substr($e->getMessage(), 0, 180),
                        'city' => null,
                        'isp' => null,
                        'org' => null,
                        'mobile' => null,
                        'fetched_at' => now(),
                    ]
                );
            }

            // ✅ Só marca enriched se o enrichment existe
            if (IpEnrichment::where('ip', $ip)->exists()) {
                $row->enriched = true;
                $row->save();
                $processedThisStep++;
            }
        }

        // Atualiza progresso pelo banco (estado real)
        $doneCount = AnaliseRunIp::where('analise_run_id', $run->id)->where('enriched', true)->count();
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
        $pending = AnaliseRunIp::where('analise_run_id', $run->id)->where('enriched', false)->exists();

        if (! $pending) {
            $run->status = 'done';
            $run->progress = 100;
            $run->processed_unique_ips = $run->total_unique_ips;
        }
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
