<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use App\Services\AnaliseInteligente\RecordsHtmlParser;
use App\Services\AnaliseInteligente\ReportAggregator;
use App\Services\AnaliseInteligente\RunStepper;
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

class AnaliseInteligenteDb extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Análise log Whatsapp';
    protected static ?string $title = 'Análise log Whatsapp';
    protected static ?string $slug = 'analise-log-wpp';

    // ✅ a view que contém o upload + botões + planilhas
    protected string $view = 'filament.pages.analise-inteligente-db-planilhas';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Investigação Telemática';
    }

    public ?array $data = [];

    public ?int $runId = null;
    public int $progress = 0;
    public bool $running = false;

    public ?array $report = null;

    /** quantos IPs enriquece por “tick” do poll */
    public int $chunkSize = 5;

    /** aba ativa (botões) */
    public string $tab = 'timeline';

    /** modal provedor */
    public ?string $selectedProvider = null;
    public array $selectedProviderIps = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('html_file')
                    ->label('Arquivo HTML (records.html)')
                    ->required()
                    ->disk('public')
                    ->directory('uploads/records-html')
                    ->acceptedFileTypes(['text/html', 'text/plain', '.html', '.htm'])
                    ->preserveFilenames()
                    ->maxSize(20_000),
            ])
            ->statePath('data');
    }

    public function gerar(): void
    {
        // reset estado
        $this->report = null;
        $this->progress = 0;
        $this->running = false;
        $this->tab = 'timeline';
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

        // agrega IPs únicos e metadados (ocorrências + last seen)
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

        // cria run + filas de IPs
        $run = DB::transaction(function () use ($parsed, $ipsMap) {
            $run = AnaliseRun::create([
                'uuid' => (string) str()->uuid(),
                'target' => $parsed['target'] ?? null,
                'total_unique_ips' => count($ipsMap),
                'processed_unique_ips' => 0,
                'progress' => 0,
                'status' => 'running',
                'report' => [
                    '_parsed' => $parsed,
                ],
            ]);

            foreach ($ipsMap as $ip => $meta) {
                AnaliseRunIp::create([
                    'analise_run_id' => $run->id,
                    'ip' => $ip,
                    'occurrences' => (int) $meta['occurrences'],
                    'last_seen_at' => $meta['last_seen_ts'] ? now()->setTimestamp((int) $meta['last_seen_ts']) : null,
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
            // processa mais um “lote”
            app(RunStepper::class)->step($run, $this->chunkSize, 1.5);
            $run->refresh();
            $this->progress = (int) $run->progress;
            $this->running = ($run->status === 'running');
        }

        // quando finalizar, monta o report (uma vez)
        if ($run->status === 'done' && $this->report === null) {
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

            Notification::make()->title('Relatório pronto')->success()->send();
        }
    }

    public function limpar(): void
    {
        $this->runId = null;
        $this->progress = 0;
        $this->running = false;
        $this->report = null;
        $this->tab = 'timeline';

        $this->selectedProvider = null;
        $this->selectedProviderIps = [];

        $this->form->fill();
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
