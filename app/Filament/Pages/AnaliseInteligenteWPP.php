<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use App\Services\AnaliseInteligente\RunStepper;
use App\Services\AnaliseInteligente\Whatsapp\RecordsHtmlParser;
use App\Services\AnaliseInteligente\Whatsapp\ReportAggregator;
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
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class AnaliseInteligenteWPP extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Análise log WHATSAPP';
    protected static ?string $title = 'Análise de log do WHATSAPP';
    protected static ?string $slug = 'analise-inteligente-wpp';

    protected string $view = 'filament.pages.analise-inteligente-wpp-planilhas';

    public ?array $data = [];
    public ?int $runId = null;
    public int $progress = 0;
    public bool $running = false;
    public ?array $report = null;
    public int $chunkSize = 5;
    public string $tab = 'timeline';

    public ?string $selectedContactType = null;
    public array $selectedContacts = [];

    public ?string $selectedProvider = null;
    public array $selectedProviderIps = [];

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
        return 2;
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
                    ->label('Arquivo HTML (records.html) ou ZIP')
                    ->required()
                    ->disk('public')
                    ->directory('uploads/records-html')
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
        if ($this->running) {
            return;
        }

        $this->report = null;
        $this->progress = 0;
        $this->tab = 'timeline';
        $this->selectedContactType = null;
        $this->selectedContacts = [];

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

        $html = $this->resolveHtmlFromUpload($storedPath);

        if (! is_string($html) || trim($html) === '') {
            Notification::make()->title('HTML vazio, inválido ou ZIP sem records.html')->danger()->send();
            return;
        }

        $parsed = (new RecordsHtmlParser())->parse($html);

        $ipsMap = [];

        foreach (($parsed['ip_events'] ?? []) as $event) {
            $ip = trim((string) ($event['ip'] ?? ''));

            if ($ip === '') {
                continue;
            }

            $time = $event['time_utc'] ?? null;
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
                'user_id' => auth()->id(),
                'uuid' => (string) str()->uuid(),
                'target' => $parsed['target'] ?? null,
                'total_unique_ips' => count($ipsMap),
                'processed_unique_ips' => 0,
                'progress' => 0,
                'status' => 'running',
                'report' => [
                    '_source' => 'whatsapp',
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
        if ($this->selectedContactType !== null) {
            return;
        }

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

        $this->selectedContactType = null;
        $this->selectedContacts = [];

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
            $enrichment = $enrs->get($ip);

            $enrichedByIp[$ip] = [
                'ip' => $ip,
                'city' => $enrichment?->city,
                'isp' => $enrichment?->isp,
                'org' => $enrichment?->org,
                'mobile' => $enrichment?->mobile,
            ];
        }

        $this->report = (new ReportAggregator())->buildReport($parsed, $enrichedByIp);
    }

    public function openContactsModal(string $type): void
    {
        $this->selectedContactType = $type;

        if ($type === 'simetricos') {
            $this->selectedContacts = $this->report['symmetric_contacts'] ?? [];
        } else {
            $this->selectedContacts = $this->report['asymmetric_contacts'] ?? [];
        }

        $this->mountAction('contactsModal');
    }

    public function contactsModal(): Action
    {
        return Action::make('contactsModal')
            ->label('Contatos')
            ->modalHeading(function () {
                return ($this->selectedContactType === 'simetricos')
                    ? 'Contatos Simétricos'
                    : 'Contatos Assimétricos';
            })
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn () => view('filament.pages.partials.modal-contacts', [
                'contacts' => $this->selectedContacts,
            ]));
    }

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

    /**
     * ✅ Resolve HTML de upload: aceita HTML direto ou ZIP (procura records.html).
     */
    private function resolveHtmlFromUpload(string $storedPath): ?string
    {
        $disk = Storage::disk('public');
        $fullPath = $disk->path($storedPath);

        if (Str::endsWith(Str::lower($storedPath), ['.html', '.htm'])) {
            $html = @file_get_contents($fullPath);
            return is_string($html) && trim($html) !== '' ? $html : null;
        }

        if (! Str::endsWith(Str::lower($storedPath), ['.zip'])) {
            return null;
        }

        $tmpDir = storage_path('app/tmp/records-' . (string) Str::uuid());

        if (! @mkdir($tmpDir, 0777, true) && ! is_dir($tmpDir)) {
            return null;
        }

        try {
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) !== true) {
                return null;
            }

            $zip->extractTo($tmpDir);
            $zip->close();

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS)
            );

            $recordsPath = null;

            foreach ($iterator as $file) {
                if ($file->isFile() && Str::lower($file->getFilename()) === 'records.html') {
                    $recordsPath = $file->getPathname();
                    break;
                }
            }

            if (! $recordsPath || ! file_exists($recordsPath)) {
                return null;
            }

            $html = @file_get_contents($recordsPath);

            return is_string($html) && trim($html) !== '' ? $html : null;
        } finally {
            $this->deleteDirectoryRecursive($tmpDir);
        }
    }

    private function deleteDirectoryRecursive(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        @rmdir($dir);
    }
}
