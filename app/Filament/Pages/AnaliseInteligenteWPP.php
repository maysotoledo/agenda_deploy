<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\Bilhetagem;
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
    public int $chunkSize = 10; // pode subir sem medo agora
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
                    ->label('Arquivos (ZIP/HTML): log/bilhetagens')
                    ->required()
                    ->multiple()
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
        if ($this->running) return;

        $this->report = null;
        $this->progress = 0;
        $this->tab = 'timeline';

        $this->selectedContactType = null;
        $this->selectedContacts = [];

        $this->selectedProvider = null;
        $this->selectedProviderIps = [];

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
            Notification::make()->title('Nenhum HTML válido ou ZIP com records.html')->danger()->send();
            return;
        }

        // ✅ principal = maior ip_events
        $mainParsed = null;
        $maxIps = -1;

        foreach ($parsedList as $item) {
            $parsed = (array) ($item['parsed'] ?? []);
            $n = count($parsed['ip_events'] ?? []);
            if ($n > $maxIps) {
                $maxIps = $n;
                $mainParsed = $parsed;
            }
        }

        if (! $mainParsed || count($mainParsed['ip_events'] ?? []) === 0) {
            Notification::make()->title('Não encontrei arquivo com IPs (ip_events vazio)')->danger()->send();
            return;
        }

        // monta IPs únicos
        $ipsMap = [];
        foreach (($mainParsed['ip_events'] ?? []) as $event) {
            $ip = trim((string) ($event['ip'] ?? ''));
            if ($ip === '') continue;

            $time = $event['time_utc'] ?? null;
            $ts = null;

            if ($time instanceof \Carbon\Carbon) $ts = $time->timestamp;
            elseif (is_string($time) && trim($time) !== '') $ts = strtotime($time) ?: null;
            elseif (is_int($time)) $ts = $time;

            if (! isset($ipsMap[$ip])) {
                $ipsMap[$ip] = ['occurrences' => 0, 'last_seen_ts' => $ts];
            }

            $ipsMap[$ip]['occurrences']++;

            if ($ts && ($ipsMap[$ip]['last_seen_ts'] === null || $ts > $ipsMap[$ip]['last_seen_ts'])) {
                $ipsMap[$ip]['last_seen_ts'] = $ts;
            }
        }

        if (count($ipsMap) === 0) {
            Notification::make()->title('Nenhum IP encontrado no arquivo principal')->warning()->send();
            return;
        }

        $run = DB::transaction(function () use ($mainParsed, $ipsMap, $parsedList) {
            $run = AnaliseRun::create([
                'user_id' => auth()->id(),
                'uuid' => (string) Str::uuid(),
                'target' => $mainParsed['target'] ?? null,
                'total_unique_ips' => count($ipsMap),
                'processed_unique_ips' => 0,
                'progress' => 0,
                'status' => 'running',
                'report' => [
                    '_source' => 'whatsapp',
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

            // ✅ Salva bilhetagem de TODOS arquivos (dedupe por recipient|message_id|timestamp)
            $seen = [];

            foreach ($parsedList as $item) {
                $p = (array) ($item['parsed'] ?? []);

                foreach (($p['message_log'] ?? []) as $m) {
                    $recipient = trim((string) ($m['recipient'] ?? ''));
                    if ($recipient === '') continue;

                    $tsUtc = $m['timestamp_utc'] ?? null;
                    $tsKey = $tsUtc instanceof \Carbon\Carbon ? $tsUtc->format('Y-m-d H:i:s') : (is_string($tsUtc) ? trim($tsUtc) : null);

                    $messageId = trim((string) ($m['message_id'] ?? ''));
                    $key = $recipient . '|' . ($messageId !== '' ? $messageId : '-') . '|' . ($tsKey ?? '-');

                    if (isset($seen[$key])) continue;
                    $seen[$key] = true;

                    Bilhetagem::create([
                        'analise_run_id' => $run->id,
                        'timestamp_utc' => $tsUtc instanceof \Carbon\Carbon ? $tsUtc : null,
                        'message_id' => $messageId !== '' ? $messageId : null,
                        'sender' => $m['sender'] ?? null,
                        'recipient' => $recipient,
                        'sender_ip' => $m['sender_ip'] ?? null,
                        'sender_port' => $m['sender_port'] ?? null,
                        'type' => $m['type'] ?? null,
                    ]);
                }
            }

            return $run;
        });

        $this->runId = $run->id;
        $this->running = true;

        Notification::make()->title('Processamento iniciado')->success()->send();
    }

    public function poll(): void
    {
        if ($this->selectedContactType !== null) return;
        if ($this->selectedProvider !== null) return;
        if (! $this->runId) return;

        $run = AnaliseRun::find($this->runId);
        if (! $run) return;

        $this->progress = (int) $run->progress;
        $this->running = ($run->status === 'running');

        if ($run->status === 'running') {
            app(RunStepper::class)->step($run, $this->chunkSize, 0.0); // ✅ sem sleep
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

        $messageLog = Bilhetagem::query()
            ->where('analise_run_id', $run->id)
            ->orderByDesc('timestamp_utc')
            ->get([
                'timestamp_utc',
                'message_id',
                'sender',
                'recipient',
                'sender_ip',
                'sender_port',
                'type',
            ])
            ->map(function (Bilhetagem $b) {
                return [
                    'timestamp_utc' => $b->timestamp_utc,
                    'message_id' => $b->message_id,
                    'sender' => $b->sender,
                    'recipient' => $b->recipient,
                    'sender_ip' => $b->sender_ip,
                    'sender_port' => $b->sender_port,
                    'type' => $b->type,
                ];
            })
            ->values()
            ->all();

        $parsed['message_log'] = $messageLog;

        $this->report = (new ReportAggregator())->buildReport($parsed, $enrichedByIp);

        if (count($this->report['bilhetagem_cards'] ?? []) > 0) {
            $this->tab = 'bilhetagem';
        }
    }

    public function openContactsModal(string $type): void
    {
        $this->selectedContactType = $type;

        $this->selectedContacts = $type === 'simetricos'
            ? ($this->report['symmetric_contacts'] ?? [])
            : ($this->report['asymmetric_contacts'] ?? []);

        $this->mountAction('contactsModal');
    }

    public function contactsModal(): Action
    {
        return Action::make('contactsModal')
            ->label('Contatos')
            ->modalHeading(fn () => ($this->selectedContactType === 'simetricos') ? 'Contatos Simétricos' : 'Contatos Assimétricos')
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

            $recordsPath = $tmpDir . DIRECTORY_SEPARATOR . 'records.html';

            if (! file_exists($recordsPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS)
                );

                $found = null;
                foreach ($iterator as $file) {
                    if ($file->isFile() && Str::lower($file->getFilename()) === 'records.html') {
                        $found = $file->getPathname();
                        break;
                    }
                }

                if (! $found) return null;
                $recordsPath = $found;
            }

            $html = @file_get_contents($recordsPath);
            return is_string($html) && trim($html) !== '' ? $html : null;
        } finally {
            $this->deleteDirectoryRecursive($tmpDir);
        }
    }

    private function deleteDirectoryRecursive(string $dir): void
    {
        if (! is_dir($dir)) return;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            if ($file->isDir()) @rmdir($file->getPathname());
            else @unlink($file->getPathname());
        }

        @rmdir($dir);
    }
}
