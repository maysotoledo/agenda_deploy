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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public int $chunkSize = 10;
    public string $tab = 'timeline';

    public ?string $selectedProvider = null;
    public array $selectedProviderIps = [];

    // Direct modal
    public ?string $selectedDirectParticipant = null;
    public array $selectedDirectMessages = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Análise Telemática';
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
                    ->label('Arquivos (ZIP/HTML): records.html')
                    ->required()
                    ->multiple()
                    ->disk('public')
                    ->directory('uploads/records-html-instagram')
                    ->acceptedFileTypes([
                        'text/html',
                        'text/plain',
                        'application/zip',
                        'application/x-zip-compressed',
                        '.html',
                        '.htm',
                        '.zip',
                    ])
                    ->preserveFilenames()
                    ->maxSize(20_000),
            ])
            ->statePath('data');
    }

    public function gerar(): void
    {
        if ($this->running) return;

        $this->report = null;
        $this->progress = 0;
        $this->running = false;
        $this->tab = 'timeline';

        $this->selectedProvider = null;
        $this->selectedProviderIps = [];
        $this->closeDirectModalState();

        $state = $this->form->getState();
        $storedPaths = $state['html_file'] ?? null;

        if (is_string($storedPaths)) $storedPaths = [$storedPaths];

        if (! is_array($storedPaths) || count($storedPaths) === 0) {
            Notification::make()->title('Envie pelo menos 1 arquivo')->danger()->send();
            return;
        }

        $disk = Storage::disk('public');

        $parsedList = [];
        foreach ($storedPaths as $storedPath) {
            if (! $storedPath || ! $disk->exists($storedPath)) continue;

            $html = $this->resolveHtmlFromUpload($storedPath);
            if (! is_string($html) || trim($html) === '') continue;

            $parsedList[] = [
                'stored_path' => $storedPath,
                'parsed' => (new RecordsHtmlParser())->parse($html),
            ];
        }

        if (count($parsedList) === 0) {
            Notification::make()->title('Nenhum HTML válido / ZIP com records.html')->danger()->send();
            return;
        }

        // principal = maior ip_events
        $mainParsed = null;
        $maxIps = -1;

        foreach ($parsedList as $item) {
            $p = (array) ($item['parsed'] ?? []);
            $n = count($p['ip_events'] ?? []);
            if ($n > $maxIps) {
                $maxIps = $n;
                $mainParsed = $p;
            }
        }

        if (! $mainParsed || count($mainParsed['ip_events'] ?? []) === 0) {
            Notification::make()->title('Não encontrei arquivo com IPs (ip_events vazio)')->danger()->send();
            return;
        }

        $ipsMap = [];
        foreach (($mainParsed['ip_events'] ?? []) as $e) {
            $ip = trim((string) ($e['ip'] ?? ''));
            if ($ip === '') continue;

            $time = $e['time_utc'] ?? null;
            $ts = null;

            if ($time instanceof \Carbon\Carbon) $ts = $time->timestamp;
            elseif (is_string($time) && trim($time) !== '') $ts = strtotime($time) ?: null;
            elseif (is_int($time)) $ts = $time;

            $ipsMap[$ip] ??= ['occurrences' => 0, 'last_seen_ts' => $ts];
            $ipsMap[$ip]['occurrences']++;

            if ($ts && ($ipsMap[$ip]['last_seen_ts'] === null || $ts > $ipsMap[$ip]['last_seen_ts'])) {
                $ipsMap[$ip]['last_seen_ts'] = $ts;
            }
        }

        if (count($ipsMap) === 0) {
            Notification::make()->title('Nenhum IP encontrado no HTML')->warning()->send();
            return;
        }

        $run = DB::transaction(function () use ($mainParsed, $ipsMap) {
            $run = AnaliseRun::create([
                'user_id' => auth()->id(),
                'uuid' => (string) Str::uuid(),
                'target' => $mainParsed['target'] ?? null,
                'total_unique_ips' => count($ipsMap),
                'processed_unique_ips' => 0,
                'progress' => 0,
                'status' => 'running',
                'report' => [
                    '_source' => 'instagram',
                    '_parsed' => $mainParsed,
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
        if ($this->selectedProvider !== null) return;
        if (! $this->runId) return;

        $run = AnaliseRun::find($this->runId);
        if (! $run) return;

        $this->progress = (int) $run->progress;
        $this->running = ($run->status === 'running');

        if ($run->status === 'running') {
            app(RunStepper::class)->step($run, $this->chunkSize, 0.0);

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
        if ($this->running) return;

        $this->runId = null;
        $this->progress = 0;
        $this->running = false;
        $this->report = null;
        $this->tab = 'timeline';

        $this->selectedProvider = null;
        $this->selectedProviderIps = [];
        $this->closeDirectModalState();

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

    // ==========================
    // ✅ Direct modal
    // ==========================
    public function openDirectModal(string $participant): void
    {
        $participant = trim($participant);

        $threads = $this->report['direct_threads'] ?? [];
        $found = null;

        foreach ((array) $threads as $t) {
            if (! is_array($t)) continue;
            if (($t['participant'] ?? null) === $participant) {
                $found = $t;
                break;
            }
        }

        $messages = is_array($found['messages'] ?? null) ? $found['messages'] : [];

        // ✅ ordena por datetime (fica natural)
        usort($messages, function ($a, $b) {
            $da = $this->safeParseDirectDatetime($a['datetime'] ?? null);
            $db = $this->safeParseDirectDatetime($b['datetime'] ?? null);

            if (! $da && ! $db) return 0;
            if (! $da) return -1;
            if (! $db) return 1;

            return $da->timestamp <=> $db->timestamp;
        });

        $this->selectedDirectParticipant = $participant;
        $this->selectedDirectMessages = $messages;

        $this->mountAction('directModal');
    }

    protected function safeParseDirectDatetime(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '—') return null;

        // formato vindo do aggregator: d/m/Y H:i:s
        try {
            return Carbon::createFromFormat('d/m/Y H:i:s', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function directModal(): Action
    {
        return Action::make('directModal')
            ->label('Conversa Direct')
            ->modalHeading(fn () => $this->selectedDirectParticipant ? $this->selectedDirectParticipant : 'Direct')
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->after(fn () => $this->closeDirectModalState())
            ->modalContent(fn () => view('filament.pages.partials.modal-direct', [
                'participant' => $this->selectedDirectParticipant,
                'messages' => $this->selectedDirectMessages,
                // ✅ alvo para alinhar no modal
                'target' => $this->report['vanity_name'] ?? ($this->report['account_identifier'] ?? null),
            ]));
    }

    protected function closeDirectModalState(): void
    {
        $this->selectedDirectParticipant = null;
        $this->selectedDirectMessages = [];
    }

    // ==========================
    // Provider modal
    // ==========================
    #[On('open-provider-ips-modal')]
    public function openProviderIpsModal(string $provider): void
    {
        $provider = trim($provider);

        $this->selectedProvider = $provider !== '' ? $provider : 'Desconhecido';
        $this->selectedProviderIps = $this->report['provider_ip_map'][$this->selectedProvider] ?? [];

        $this->mountAction('providerIpsModal');
    }

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

    // ==========================
    // ZIP/HTML helper
    // ==========================
    protected function resolveHtmlFromUpload(string $storedPath): ?string
    {
        $disk = Storage::disk('public');
        $fullPath = $disk->path($storedPath);

        if (! is_file($fullPath)) return null;

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if ($ext === 'zip') {
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) !== true) return null;

            $htmlContent = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (! is_string($name)) continue;

                if (str_ends_with(strtolower($name), 'records.html')) {
                    $htmlContent = $zip->getFromIndex($i);
                    break;
                }
            }

            if (! $htmlContent) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (! is_string($name)) continue;

                    if (str_ends_with(strtolower($name), '.html') || str_ends_with(strtolower($name), '.htm')) {
                        $htmlContent = $zip->getFromIndex($i);
                        break;
                    }
                }
            }

            $zip->close();

            return is_string($htmlContent) ? $htmlContent : null;
        }

        $html = @file_get_contents($fullPath);

        return is_string($html) ? $html : null;
    }
}
