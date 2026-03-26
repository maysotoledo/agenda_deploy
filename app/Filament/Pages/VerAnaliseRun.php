<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use App\Services\AnaliseInteligente\ReportAggregator;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\On;

class VerAnaliseRun extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = null;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Relatório Processado';
    protected static ?string $slug = 'ver-analise-run/{run}';

    protected string $view = 'filament.pages.ver-analise-run-planilhas';

    public AnaliseRun $runModel;
    public ?array $report = null;

    public string $tab = 'timeline';

    public ?string $selectedProvider = null;
    public array $selectedProviderIps = [];

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

        $this->report = (new ReportAggregator())->buildReport($parsed, $enrichedByIp);
    }

    #[On('open-provider-from-table')]
    public function openProviderFromTable(string $provider): void
    {
        $this->openProvider($provider);
    }

    public function openProvider(string $provider): void
    {
        $this->selectedProvider = $provider;
        $this->selectedProviderIps = $this->report['provider_ip_map'][$provider] ?? [];
        $this->mountAction('providerIpsModal');
    }

    public function providerIpsModal(): Action
    {
        return Action::make('providerIpsModal')
            ->label('IPs do Provedor')
            ->modalHeading(fn () => 'IPs do provedor: ' . ($this->selectedProvider ?? ''))
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn () => view('filament.pages.partials.modal-provider-ips', [
                'rows' => $this->selectedProviderIps,
            ]));
    }
}
