<?php

namespace App\Services\AnaliseInteligente;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RunStepper
{
    /**
     * Processa um "chunk" de IPs por chamada (poll).
     * $sleepSeconds fica opcional — evite sleeps longos em request web.
     */
    public function step(AnaliseRun $run, int $chunkSize = 5, float $sleepSeconds = 0.0): void
    {
        $deadline = microtime(true) + 12.0;

        // evita rodar em run finalizado
        if (($run->status ?? null) !== 'running') {
            return;
        }

        $total = (int) ($run->total_unique_ips ?? 0);
        if ($total <= 0) {
            $this->finishRun($run);
            return;
        }

        // pega pendentes
        $ips = AnaliseRunIp::query()
            ->where('analise_run_id', $run->id)
            ->where(function ($q) {
                // compatível com boolean e/ou null
                $q->where('enriched', false)->orWhereNull('enriched');
            })
            ->limit(max(1, min($chunkSize, 3)))
            ->get();

        // se não tem mais pendente => finaliza
        if ($ips->count() === 0) {
            $this->finishRun($run);
            return;
        }

        $processedNow = 0;

        foreach ($ips as $row) {
            if (microtime(true) >= $deadline) {
                break;
            }

            try {
                $this->processIpRow($row);
            } catch (\Throwable $e) {
                // NUNCA deixar travar o poll.
                Log::warning('RunStepper: erro ao processar IP', [
                    'run_id' => $run->id,
                    'ip' => $row->ip ?? null,
                    'error' => $e->getMessage(),
                ]);

                // marca como "processado" mesmo assim, para não ficar preso eternamente
                $row->enriched = true;
                $row->save();
            }

            $processedNow++;

            if ($sleepSeconds > 0) {
                usleep((int) round($sleepSeconds * 1_000_000));
            }
        }

        // atualiza contadores e progresso
        $run->refresh();

        $already = (int) ($run->processed_unique_ips ?? 0);
        $newProcessed = $already + $processedNow;

        if ($newProcessed > $total) $newProcessed = $total;

        $run->processed_unique_ips = $newProcessed;
        $run->progress = (int) floor(($newProcessed / $total) * 100);

        // se chegou no fim, finaliza
        if ($newProcessed >= $total) {
            $this->finishRun($run);
        } else {
            $run->status = 'running';
            $run->save();
        }
    }

    private function processIpRow(AnaliseRunIp $row): void
    {
        $ip = trim((string) ($row->ip ?? ''));
        if ($ip === '') {
            $row->enriched = true;
            $row->save();
            return;
        }

        // tenta reutilizar enrichment existente
        $existing = IpEnrichment::query()->where('ip', $ip)->first();
        if ($existing) {
            $row->enriched = true;
            $row->save();
            return;
        }

        // ✅ Enrichment via HTTP (não pode travar)
        $data = $this->fetchEnrichment($ip);

        // fallback seguro
        $payload = [
            'ip' => $ip,
            'city' => $data['city'] ?? null,
            'isp' => $data['isp'] ?? null,
            'org' => $data['org'] ?? null,
            'mobile' => (bool) ($data['mobile'] ?? false),
        ];

        IpEnrichment::updateOrCreate(
            ['ip' => $ip],
            $payload,
        );

        $row->enriched = true;
        $row->save();
    }

    private function fetchEnrichment(string $ip): array
    {
        // Exemplo usando ip-api.com (se você usa outro provedor, troque aqui)
        // Importante: timeout curto + tratamento de erro
        try {
            $resp = Http::connectTimeout(1)
                ->timeout(2)
                ->get('http://ip-api.com/json/' . $ip, [
                    'fields' => 'status,message,city,isp,org,mobile',
                ]);

            if (! $resp->successful()) {
                return [];
            }

            $json = $resp->json();
            if (! is_array($json)) return [];

            if (($json['status'] ?? null) !== 'success') {
                return [];
            }

            return $json;
        } catch (\Throwable) {
            return [];
        }
    }

    private function finishRun(AnaliseRun $run): void
    {
        $total = (int) ($run->total_unique_ips ?? 0);

        // garante progresso coerente
        $run->processed_unique_ips = $total;
        $run->progress = 100;
        $run->status = 'done';
        $run->save();
    }
}
