<?php

namespace App\Services\AnaliseInteligente\Platform;

use App\Services\AnaliseInteligente\Generic\GenericReportAggregator;

class PlatformReportAggregator
{
    public function buildReport(array $parsed, array $enrichedByIp): array
    {
        $report = (new GenericReportAggregator())->buildReport($parsed, $enrichedByIp);
        $report['total_unique_ips'] = $this->countUniqueIps($parsed['events'] ?? []);

        $report['platform_label'] = $parsed['platform_label'] ?? 'Provedor';
        $report['accounts_found'] = array_values((array) ($parsed['emails'] ?? []));
        $report['phones_found'] = array_values((array) ($parsed['phones'] ?? []));
        $report['identifiers_found'] = array_values((array) ($parsed['identifiers'] ?? []));
        $report['investigation_hints'] = $this->buildHints($report);

        return $report;
    }

    private function countUniqueIps(array $events): int
    {
        $ips = [];

        foreach ($events as $event) {
            $ip = trim((string) ($event['ip'] ?? ''));
            if ($ip !== '') {
                $ips[$ip] = true;
            }
        }

        return count($ips);
    }

    private function buildHints(array $report): array
    {
        $hints = [];

        if (($report['total_events'] ?? 0) > 0 && ($report['total_unique_ips'] ?? 0) === 0) {
            $hints[] = 'Há eventos, mas nenhum IP foi enriquecido. Verifique se os IPs extraídos são válidos/publicáveis.';
        }

        if (($report['night_total_events'] ?? 0) > 0) {
            $hints[] = 'Há eventos no período noturno (23h às 06h), úteis para análise de vínculo residencial.';
        }

        if (($report['mobile_total_events'] ?? 0) > 0) {
            $hints[] = 'Há eventos em conexão móvel, o que pode exigir requisição à operadora com data, hora, IP e porta lógica quando disponível.';
        }

        if (count($report['accounts_found'] ?? []) > 1) {
            $hints[] = 'Mais de uma conta/e-mail foi encontrado no log. Confira se todos pertencem ao mesmo alvo.';
        }

        if (count($report['identifiers_found'] ?? []) > 0) {
            $hints[] = 'Foram encontrados identificadores de dispositivo/conta. Use-os para complementar requisições futuras.';
        }

        return $hints;
    }
}
