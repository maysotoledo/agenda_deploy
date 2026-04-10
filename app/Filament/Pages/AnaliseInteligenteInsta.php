<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use App\Services\AnaliseInteligente\Instagram\RecordsHtmlParser;
use App\Services\AnaliseInteligente\Instagram\ReportAggregator;
use App\Services\AnaliseInteligente\RunStepper;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class AnaliseInteligenteInsta extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-camera';
    protected static ?string $navigationLabel = 'Análise log INSTAGRAM';
    protected static ?string $title = 'Análise de log do INSTAGRAM';
    protected static ?string $slug = 'analise-inteligente-insta';

    protected string $view = 'filament.pages.analise-inteligente-insta-planilhas';

    public ?array $data = [];
    public ?int $runId = null;
    public int $progress = 0;
    public bool $running = false;
    public ?array $report = null;
    public int $chunkSize = 5;
    public string $tab = 'timeline';

    // ✅ NOVO: estado do modal de provedor
    public ?string $selectedProvider = null;
    public array $selectedProviderIps = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Investigação Telemática';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public function mount(): void
    {
        $this->form->fill();

        $runId = request()->integer('run');

        if ($runId) {
            $this->loadExistingRun($runId);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('html_file')
                    ->label('Arquivo HTML (records.html)')
                    ->required()
                    ->disk('public')
                    ->directory('uploads/records-html-instagram')
                    ->acceptedFileTypes(['text/html', 'text/plain', '.html', '.htm'])
                    ->preserveFilenames()
                    ->maxSize(20_000),
            ])
            ->statePath('data');
    }

    public function gerar(): void
    {
        if ($this->running) {
            return;
        }

        $this->report = null;
        $this->progress = 0;
        $this->tab = 'timeline';

        // ✅ reset modal provedor
        $this->selectedProvider = null;
        $this->selectedProviderIps = [];

        $state = $this->form->getState();
        $storedPath = $state['html_file'] ?? null;

        if (is_array($storedPath)) {
            $storedPath = $storedPath[0] ?? null;
        }

        if (! $storedPath || ! Storage::disk('public')->exists($storedPath)) {
            Notification::make()->title('Arquivo não encontrado')->danger()->send();
            return;
        }

        $html = file_get_contents(Storage::disk('public')->path($storedPath));

        if (! is_string($html) || trim($html) === '') {
            Notification::make()->title('HTML vazio ou inválido')->danger()->send();
            return;
        }

        $parsed = (new RecordsHtmlParser())->parse($html);

        $ipsMap = [];

        foreach (($parsed['ip_events'] ?? []) as $e) {
            $ip = trim((string) ($e['ip'] ?? ''));

            if ($ip === '') {
                continue;
            }

            $time = $e['time_utc'] ?? null;
            $ts = null;

            if ($time instanceof \Carbon\Carbon) {
                $ts = $time->timestamp;
            } elseif (is_string($time) && trim($time) !== '') {
                $ts = strtotime($time) ?: null;
            } elseif (is_int($time)) {
                $ts = $time;
            }

            if (! isset($ipsMap[$ip])) {
                $ipsMap[$ip] = [
                    'occurrences' => 0,
                    'last_seen_ts' => $ts,
                ];
            }

            $ipsMap[$ip]['occurrences']++;

            if ($ts && ($ipsMap[$ip]['last_seen_ts'] === null || $ts > $ipsMap[$ip]['last_seen_ts'])) {
                $ipsMap[$ip]['last_seen_ts'] = $ts;
            }
        }

        if (count($ipsMap) === 0) {
            Notification::make()->title('Nenhum IP encontrado no HTML')->warning()->send();
            return;
        }

        $run = DB::transaction(function () use ($parsed, $ipsMap) {
            $run = AnaliseRun::create([
                'uuid' => (string) str()->uuid(),
                'target' => $parsed['target'] ?? null,
                'total_unique_ips' => count($ipsMap),
                'processed_unique_ips' => 0,
                'progress' => 0,
                'status' => 'running',
                'report' => [
                    '_source' => 'instagram',
                    '_parsed' => $parsed,
                ],
            ]);

            foreach ($ipsMap as $ip => $meta) {
                AnaliseRunIp::create([
                    'analise_run_id' => $run->id,
                    'ip' => $ip,
                    'occurrences' => (int) $meta['occurrences'],
                    'last_seen_at' => $meta['last_seen_ts']
                        ? now()->setTimestamp((int) $meta['last_seen_ts'])
                        : null,
                    'enriched' => false,
                ]);
            }

            return $run;
        });

        $this->runId = $run->id;
        $this->running = true;

        Notification::make()->title('Processamento iniciado')->success()->send();
    }

    public function poll(): void
    {
        // ✅ se o modal de provedor estiver aberto, não re-renderiza
        if ($this->selectedProvider !== null) {
            return;
        }

        if (! $this->runId) {
            return;
        }

        $run = AnaliseRun::find($this->runId);

        if (! $run) {
            return;
        }

        $this->progress = (int) $run->progress;
        $this->running = ($run->status === 'running');

        if ($run->status === 'running') {
            app(RunStepper::class)->step($run, $this->chunkSize, 1.5);

            $run->refresh();
            $this->progress = (int) $run->progress;
            $this->running = ($run->status === 'running');
        }

        if ($run->status === 'done' && $this->report === null) {
            $this->hydrateReportFromRun($run);

            Notification::make()->title('Relatório pronto')->success()->send();
        }
    }

    public function limpar(): void
    {
        if ($this->running) {
            return;
        }

        $this->runId = null;
        $this->progress = 0;
        $this->running = false;
        $this->report = null;
        $this->tab = 'timeline';

        // ✅ reset modal provedor
        $this->selectedProvider = null;
        $this->selectedProviderIps = [];

        $this->form->fill();
    }

    protected function loadExistingRun(int $runId): void
    {
        $run = AnaliseRun::find($runId);

        if (! $run) {
            Notification::make()->title('Relatório processado não encontrado')->danger()->send();
            return;
        }

        $this->runId = $run->id;
        $this->progress = (int) $run->progress;
        $this->running = ($run->status === 'running');

        if ($run->status === 'done') {
            $this->hydrateReportFromRun($run);
        }
    }

    protected function hydrateReportFromRun(AnaliseRun $run): void
    {
        $parsed = $run->report['_parsed'] ?? null;

        if (! is_array($parsed)) {
            Notification::make()->title('Sem dados para montar relatório')->danger()->send();
            return;
        }

        $ips = AnaliseRunIp::where('analise_run_id', $run->id)->pluck('ip')->all();
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

    // ✅ NOVO: escuta o evento disparado pela ProvidersStatsTable
    #[On('open-provider-ips-modal')]
    public function openProviderIpsModal(string $provider): void
    {
        $provider = trim($provider);

        $this->selectedProvider = $provider !== '' ? $provider : 'Desconhecido';
        $this->selectedProviderIps = $this->report['provider_ip_map'][$this->selectedProvider] ?? [];

        $this->mountAction('providerIpsModal');
    }

    // ✅ NOVO: modal de IPs do provedor
    public function providerIpsModal(): Action
    {
        return Action::make('providerIpsModal')
            ->label('IPs do Provedor')
            ->modalHeading(fn () => "IPs - {$this->selectedProvider}")
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->after(fn () => $this->closeProviderModalState())
            ->modalContent(fn () => view('filament.pages.partials.modal-provider-ips', [
                'rows' => $this->selectedProviderIps,
            ]));
    }

    protected function closeProviderModalState(): void
    {
        $this->selectedProvider = null;
        $this->selectedProviderIps = [];
    }
}
