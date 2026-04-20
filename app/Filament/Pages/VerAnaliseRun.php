<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use App\Services\AnaliseInteligente\ReportAggregator;
use Filament\Pages\Page;

class VerAnaliseRun extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = null;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Ver Analise Run';
    protected static ?string $slug = 'ver-analise-run/{run}';

    protected string $view = 'filament.pages.ver-analise-run-planilhas';

    public AnaliseRun $runModel;
    public ?array $report = null;

    public string $tab = 'timeline';

    public function mount(string|int $run): void
    {
        $this->runModel = AnaliseRun::findOrFail((int) $run);
        $this->tab = 'timeline';

        $this->loadReport();
    }

    private function loadReport(): void
    {
        $parsed = $this->runModel->report['_parsed'] ?? null;
        if (! is_array($parsed)) {
            $this->report = null;
            return;
        }

        // mesmos enrichments usados na geração
        $ips = AnaliseRunIp::where('analise_run_id', $this->runModel->id)->pluck('ip')->all();
        $enrs = IpEnrichment::whereIn('ip', $ips)->get()->keyBy('ip');

        $enrichedByIp = [];
        foreach ($ips as $ip) {
            $e = $enrs->get($ip);
            $enrichedByIp[$ip] = [
                'ip' => $ip,
                'city' => $e?->city,
                'isp' => $e?->isp,
                'org' => $e?->org,
                'mobile' => $e?->mobile,
            ];
        }

        // ✅ usa o MESMO aggregator (o completo)
        $this->report = (new ReportAggregator())->buildReport($parsed, $enrichedByIp);
    }
}
