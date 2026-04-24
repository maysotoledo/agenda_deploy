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

    protected function countUniqueIps(array $events): int
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

    protected function buildHints(array $report): array
    {
        $hints = [];

        if (($report['total_events'] ?? 0) > 0 && ($report['total_unique_ips'] ?? 0) === 0) {
            $hints[] = 'Ha eventos, mas nenhum IP foi enriquecido. Verifique se os IPs extraidos sao validos/publicaveis.';
        }

        if (($report['night_total_events'] ?? 0) > 0) {
            $hints[] = 'Ha eventos no periodo noturno (23h as 06h), uteis para analise de vinculo residencial.';
        }

        if (($report['mobile_total_events'] ?? 0) > 0) {
            $hints[] = 'Ha eventos em conexao movel, o que pode exigir requisicao a operadora com data, hora, IP e porta logica quando disponivel.';
        }

        if (count($report['accounts_found'] ?? []) > 1) {
            $hints[] = 'Mais de uma conta/e-mail foi encontrado no log. Confira se todos pertencem ao mesmo alvo.';
        }

        if (count($report['identifiers_found'] ?? []) > 0 || count($report['device_identifier_rows'] ?? []) > 0) {
            $hints[] = 'Foram encontrados identificadores de dispositivo/conta. Use-os para complementar requisicoes futuras.';
        }

        if (count($report['maps_rows'] ?? []) > 0) {
            $hints[] = 'Foram encontradas atividades do Google Maps. Verifique rotas, pesquisas e coordenadas para compor deslocamentos do alvo.';
        }

        if (count($report['search_rows'] ?? []) > 0) {
            $hints[] = 'Foram encontradas pesquisas no Google Search. Analise termos, horarios e recorrencias para identificar interesses e intencoes do alvo.';
        }

        if (($report['weekend_total_events'] ?? 0) > 0) {
            $hints[] = 'Ha acessos em fim de semana, uteis para compor padrao de uso pessoal/residencial.';
        }

        return $hints;
    }
}
