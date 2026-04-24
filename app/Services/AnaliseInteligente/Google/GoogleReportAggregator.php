<?php

namespace App\Services\AnaliseInteligente\Google;

use App\Services\AnaliseInteligente\Platform\PlatformReportAggregator;

class GoogleReportAggregator extends PlatformReportAggregator
{
    public function buildReport(array $parsed, array $enrichedByIp): array
    {
        $report = parent::buildReport($parsed, $enrichedByIp);
        $report['subscriber_info'] = $parsed['google_subscriber_info'] ?? ($report['subscriber_info'] ?? null);
        $report['google_activity_is_supplemental'] = true;
        $report['investigation_hints'] = $this->buildHints($report);

        return $report;
    }

    protected function buildHints(array $report): array
    {
        $hints = parent::buildHints($report);
        $subscriber = $report['subscriber_info'] ?? null;

        if (! is_array($subscriber)) {
            return $hints;
        }

        if (! empty($subscriber['recovery_email'])) {
            $hints[] = 'E-mail de recuperacao encontrado: ' . $subscriber['recovery_email'] . '. Cruce com outros logs/contas do investigado.';
        }

        if (! empty($subscriber['recovery_sms'])) {
            $hints[] = 'Telefone de recuperacao encontrado: ' . $subscriber['recovery_sms'] . '. Priorize cruzamento com operadora e apps de mensageria.';
        }

        if (! empty($subscriber['terms_of_service_ip'])) {
            $hints[] = 'IP de criacao/aceite dos termos encontrado: ' . $subscriber['terms_of_service_ip'] . '. Pode indicar origem inicial da conta.';
        }

        if (($report['total_events'] ?? 0) > 0 && ((int) ($report['_counts']['maps'] ?? 0) > 0 || (int) ($report['_counts']['search'] ?? 0) > 0)) {
            $hints[] = 'No Google, SubscriberInfo e a base principal de IP/atividade. MyActivity entra apenas como complemento de Maps e Pesquisas do mesmo alvo.';
        }

        return $hints;
    }
}
