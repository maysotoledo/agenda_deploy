<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunContact;
use App\Models\AnaliseRunIp;
use App\Models\Bilhetagem;
use App\Models\IpEnrichment;
use App\Services\AnaliseInteligente\RunStepper;
use App\Services\AnaliseInteligente\Whatsapp\RecordsHtmlParser;
use App\Services\AnaliseInteligente\Whatsapp\ReportAggregator;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
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

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Análise log WHATSAPP';
    protected static ?string $title = 'Análise de log do WHATSAPP';
    protected static ?string $slug = 'analise-inteligente-wpp';

    protected string $view = 'filament.pages.analise-inteligente-wpp-planilhas';

    public ?array $data = [];

    public ?int $runId = null;
    public int $progress = 0;
    public bool $running = false;
    public ?array $report = null;

    public int $chunkSize = 10;
    public string $tab = 'timeline';

    public ?string $selectedContactType = null;
    public array $selectedContacts = [];

    public ?string $selectedProvider = null;
    public array $selectedProviderIps = [];

    public array $runWarnings = [];

    // ====== nomes ======
    public array $contactNames = [];
    public ?string $selectedContactPhone = null;

    // ====== MODAL BILHETAGEM (paginado) ======
    public ?string $bilhetagemModalPhone = null;     // digits
    public ?string $bilhetagemModalPhoneRaw = null;  // raw
    public int $bilhetagemModalPage = 1;
    public int $bilhetagemModalPerPage = 10;
    public int $bilhetagemModalTotal = 0;
    public array $bilhetagemModalRows = [];


    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Análise Telemática';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
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

    // =========================================================
    // ✅ GERAR
    // =========================================================
    public function gerar(): void
    {
        if ($this->running) return;

        $this->report = null;
        $this->progress = 0;
        $this->running = false;
        $this->tab = 'timeline';
        $this->runWarnings = [];

        $this->selectedContactType = null;
        $this->selectedContacts = [];
        $this->selectedProvider = null;
        $this->selectedProviderIps = [];

        $this->resetBilhetagemModalState();

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
                'html' => $html,
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

        $runTargetRaw = $mainParsed['target'] ?? ($mainParsed['account_identifier'] ?? null);

        // ips do principal
        $ipsMap = [];
        foreach (($mainParsed['ip_events'] ?? []) as $event) {
            $ip = trim((string) ($event['ip'] ?? ''));
            if ($ip === '') continue;

            $time = $event['time_utc'] ?? null;
            $ts = null;

            if ($time instanceof Carbon) $ts = $time->timestamp;
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

        $connectionIpWithPort = data_get($mainParsed, 'connection_info.last_ip');
        $connectionIpBase = $this->extractIpBase(is_string($connectionIpWithPort) ? $connectionIpWithPort : null);

        $connectionLastSeenUtc = data_get($mainParsed, 'connection_info.last_seen_utc');
        $connectionLastSeenTs = null;
        if ($connectionLastSeenUtc instanceof Carbon) $connectionLastSeenTs = $connectionLastSeenUtc->timestamp;
        elseif (is_string($connectionLastSeenUtc) && trim($connectionLastSeenUtc) !== '') $connectionLastSeenTs = strtotime($connectionLastSeenUtc) ?: null;

        // warnings bilhetagem alvo diferente
        $ignoredBilhetagens = [];
        foreach ($parsedList as $item) {
            $p = (array) ($item['parsed'] ?? []);
            if (count($p['message_log'] ?? []) === 0) continue;

            $fileTarget = $p['target'] ?? ($p['account_identifier'] ?? null);
            if (! $this->targetsMatch(is_string($runTargetRaw) ? $runTargetRaw : null, is_string($fileTarget) ? $fileTarget : null)) {
                $ignoredBilhetagens[] = [
                    'arquivo' => (string) ($item['stored_path'] ?? ''),
                    'alvo_arquivo' => is_string($fileTarget) ? $fileTarget : '-',
                    'alvo_relatorio' => is_string($runTargetRaw) ? $runTargetRaw : '-',
                ];
            }
        }

        $this->runWarnings = $ignoredBilhetagens;

        $run = DB::transaction(function () use (
            $mainParsed,
            $ipsMap,
            $connectionIpBase,
            $connectionLastSeenTs,
            $parsedList,
            $runTargetRaw,
            $ignoredBilhetagens
        ) {
            $run = AnaliseRun::create([
                'user_id' => auth()->id(),
                'uuid' => (string) Str::uuid(),
                'target' => $mainParsed['target'] ?? null,
                'total_unique_ips' => count($ipsMap) + ($connectionIpBase && ! isset($ipsMap[$connectionIpBase]) ? 1 : 0),
                'processed_unique_ips' => 0,
                'progress' => 0,
                'status' => 'running',
                'report' => [
                    '_source' => 'whatsapp',
                    '_parsed' => $mainParsed,
                    '_warnings' => [
                        'ignored_bilhetagens' => $ignoredBilhetagens,
                    ],
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

            if ($connectionIpBase && ! isset($ipsMap[$connectionIpBase])) {
                AnaliseRunIp::create([
                    'analise_run_id' => $run->id,
                    'ip' => $connectionIpBase,
                    'occurrences' => 0,
                    'last_seen_at' => $connectionLastSeenTs ? now()->setTimestamp((int) $connectionLastSeenTs) : null,
                    'enriched' => false,
                ]);
            }

            // importa bilhetagem só se alvo bater
            $seen = [];

            foreach ($parsedList as $item) {
                $p = (array) ($item['parsed'] ?? []);
                $fileTarget = $p['target'] ?? ($p['account_identifier'] ?? null);

                $match = $this->targetsMatch(
                    is_string($runTargetRaw) ? $runTargetRaw : null,
                    is_string($fileTarget) ? $fileTarget : null
                );

                if (! $match) continue;

                foreach (($p['message_log'] ?? []) as $m) {
                    $recipient = trim((string) ($m['recipient'] ?? ''));
                    if ($recipient === '') continue;

                    $tsUtc = $m['timestamp_utc'] ?? null;
                    $tsKey = $tsUtc instanceof Carbon ? $tsUtc->format('Y-m-d H:i:s') : '-';

                    $messageId = trim((string) ($m['message_id'] ?? ''));
                    $key = $recipient . '|' . ($messageId !== '' ? $messageId : '-') . '|' . $tsKey;

                    if (isset($seen[$key])) continue;
                    $seen[$key] = true;

                    Bilhetagem::create([
                        'analise_run_id' => $run->id,
                        'timestamp_utc' => $tsUtc instanceof Carbon ? $tsUtc : null,
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

    // =========================================================
    // ✅ POLL
    // =========================================================
    public function poll(): void
    {
        if (! $this->runId) return;
        if ($this->selectedContactType !== null) return;
        if ($this->selectedProvider !== null) return;

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
            $this->tab = 'timeline';

            $ignored = data_get($run->report, '_warnings.ignored_bilhetagens', []);
            $this->runWarnings = is_array($ignored) ? $ignored : [];

            if (is_array($ignored) && count($ignored) > 0) {
                $lines = [];
                foreach ($ignored as $w) {
                    $lines[] = "Arquivo: " . ($w['arquivo'] ?? '-') .
                        " | Alvo do arquivo: " . ($w['alvo_arquivo'] ?? '-') .
                        " | Alvo do relatório: " . ($w['alvo_relatorio'] ?? '-');
                }

                Notification::make()
                    ->title('Bilhetagem ignorada: alvo diferente do log')
                    ->body(implode("\n", $lines))
                    ->warning()
                    ->send();
            }

            Notification::make()->title('Relatório pronto')->success()->send();
        }
    }

    // =========================================================
    // ✅ Contatos (Simétricos / Assimétricos) - Modal (CORRIGIDO)
    // =========================================================
    public function openContactsModal(string $type): void
    {
        $type = trim($type);

        if (! in_array($type, ['simetricos', 'assimetricos'], true)) {
            Notification::make()->title('Tipo de contato inválido')->danger()->send();
            return;
        }

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
            ->modalHeading(fn () => $this->selectedContactType === 'simetricos'
                ? 'Contatos Simétricos'
                : 'Contatos Assimétricos'
            )
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn () => view('filament.pages.partials.modal-contacts', [
                'contacts' => $this->selectedContacts,
                'type' => $this->selectedContactType,
            ]));
    }

    // =========================================================
    // ✅ Provedor -> Modal IPs (caso seu front dispare)
    // =========================================================
    #[On('open-provider-ips-modal')]
    public function openProviderIpsModal(string $provider): void
    {
        $provider = trim((string) $provider);
        $this->selectedProvider = $provider !== '' ? $provider : 'Desconhecido';
        $this->selectedProviderIps = $this->report['provider_ip_map'][$this->selectedProvider] ?? [];

        $this->mountAction('providerIpsModal');
    }

    public function providerIpsModal(): Action
    {
        return Action::make('providerIpsModal')
            ->label('IPs do Provedor')
            ->modalHeading(fn () => 'IPs - ' . ($this->selectedProvider ?? 'Desconhecido'))
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->after(fn () => $this->closeProviderModalState())
            ->modalContent(fn () => view('filament.pages.partials.modal-provider-ips', [
                'rows' => $this->selectedProviderIps,
                'provider' => $this->selectedProvider,
            ]));
    }

    protected function closeProviderModalState(): void
    {
        $this->selectedProvider = null;
        $this->selectedProviderIps = [];
    }

    // =========================================================
    // LIMPAR
    // =========================================================
    public function limpar(): void
    {
        if ($this->running) return;

        $this->runId = null;
        $this->progress = 0;
        $this->running = false;
        $this->report = null;
        $this->runWarnings = [];

        $this->tab = 'timeline';

        $this->selectedContactType = null;
        $this->selectedContacts = [];
        $this->selectedProvider = null;
        $this->selectedProviderIps = [];

        $this->contactNames = [];
        $this->selectedContactPhone = null;

        $this->resetBilhetagemModalState();

        $this->form->fill();
    }

    // =========================================================
    // Upload bilhetagem (Action)
    // =========================================================
    public function bilhetagemUpload(): Action
    {
        return Action::make('bilhetagemUpload')
            ->label('Upload bilhetagem')
            ->modalHeading('Enviar arquivo de bilhetagem (ZIP/HTML)')
            ->modalSubmitActionLabel('Importar')
            ->form([
                FileUpload::make('bilhetagem_file')
                    ->label('Arquivo (ZIP/HTML)')
                    ->required()
                    ->multiple()
                    ->disk('public')
                    ->directory('uploads/records-html-bilhetagem')
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
            ->action(function (array $data) {
                $this->importarSomenteBilhetagem($data['bilhetagem_file'] ?? []);
            });
    }

    protected function importarSomenteBilhetagem(array|string|null $storedPaths): void
    {
        if (! $this->runId) {
            Notification::make()->title('Você precisa gerar um relatório antes.')->danger()->send();
            return;
        }

        $run = AnaliseRun::find($this->runId);
        if (! $run) {
            Notification::make()->title('Run não encontrado.')->danger()->send();
            return;
        }

        if (is_string($storedPaths)) $storedPaths = [$storedPaths];
        if (! is_array($storedPaths) || count($storedPaths) === 0) {
            Notification::make()->title('Envie pelo menos 1 arquivo.')->danger()->send();
            return;
        }

        $disk = Storage::disk('public');
        $runTargetRaw = $run->target ?: (data_get($run->report, '_parsed.target') ?: data_get($run->report, '_parsed.account_identifier'));

        foreach ($storedPaths as $storedPath) {
            if (! $storedPath || ! $disk->exists($storedPath)) continue;

            $html = $this->resolveHtmlFromUpload($storedPath);
            if (! is_string($html) || trim($html) === '') {
                Notification::make()->title('Arquivo inválido (não foi possível ler HTML/ZIP).')->danger()->send();
                return;
            }

            $parsed = (new RecordsHtmlParser())->parse($html);
            $fileTargetRaw = $parsed['target'] ?? ($parsed['account_identifier'] ?? null);

            if (! $this->targetsMatch(is_string($runTargetRaw) ? $runTargetRaw : null, is_string($fileTargetRaw) ? $fileTargetRaw : null)) {
                Notification::make()
                    ->title('Bilhetagem não pertence ao alvo deste relatório')
                    ->body("Alvo do relatório: {$runTargetRaw}\nAlvo do arquivo: {$fileTargetRaw}")
                    ->danger()
                    ->send();
                return;
            }
        }

        // dedupe existente
        $existing = Bilhetagem::query()
            ->where('analise_run_id', $run->id)
            ->get(['recipient', 'message_id', 'timestamp_utc'])
            ->mapWithKeys(function ($b) {
                $ts = $b->timestamp_utc?->format('Y-m-d H:i:s') ?? '-';
                $mid = $b->message_id ?: '-';
                $rec = trim((string) $b->recipient);
                return [$rec . '|' . $mid . '|' . $ts => true];
            })
            ->all();

        $seen = $existing;
        $inserted = 0;

        foreach ($storedPaths as $storedPath) {
            if (! $storedPath || ! $disk->exists($storedPath)) continue;

            $html = $this->resolveHtmlFromUpload($storedPath);
            if (! is_string($html) || trim($html) === '') continue;

            $parser = new RecordsHtmlParser();
            $messageLog = $parser->parseBilhetagemOnly($html);

            foreach (($messageLog ?? []) as $m) {
                $recipient = trim((string) ($m['recipient'] ?? ''));
                if ($recipient === '') continue;

                $tsUtc = $m['timestamp_utc'] ?? null;
                $tsKey = $tsUtc instanceof Carbon ? $tsUtc->format('Y-m-d H:i:s') : '-';

                $messageId = trim((string) ($m['message_id'] ?? ''));
                $key = $recipient . '|' . ($messageId !== '' ? $messageId : '-') . '|' . $tsKey;

                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                Bilhetagem::create([
                    'analise_run_id' => $run->id,
                    'timestamp_utc' => $tsUtc instanceof Carbon ? $tsUtc : null,
                    'message_id' => $messageId !== '' ? $messageId : null,
                    'sender' => $m['sender'] ?? null,
                    'recipient' => $recipient,
                    'sender_ip' => $m['sender_ip'] ?? null,
                    'sender_port' => $m['sender_port'] ?? null,
                    'type' => $m['type'] ?? null,
                ]);

                $inserted++;
            }
        }

        // ✅ garante sender_ip em run_ips e enriquece
        $this->ensureRunIpsForBilhetagem($run);
        $this->enrichPendingIpsNow($run);

        $this->hydrateReportFromRun($run);
        $this->tab = 'bilhetagem';

        Notification::make()
            ->title("Bilhetagem importada: {$inserted} registros novos")
            ->success()
            ->send();
    }

    // =========================================================
    // Modal mensagens bilhetagem
    // =========================================================
    public function openBilhetagemMessagesModal(string $phone): void
    {
        if (! $this->runId) {
            Notification::make()->title('Run inválido')->danger()->send();
            return;
        }

        $raw = trim($phone);
        $k = $this->normalizePhoneKey($raw);
        if (! $k) {
            Notification::make()->title('Contato inválido')->danger()->send();
            return;
        }

        $this->bilhetagemModalPhone = $k;
        $this->bilhetagemModalPhoneRaw = $raw;
        $this->bilhetagemModalPage = 1;

        $this->loadBilhetagemModalPage();
        $this->mountAction('bilhetagemMessagesModal');
    }

    public function bilhetagemMessagesModal(): Action
    {
        return Action::make('bilhetagemMessagesModal')
            ->label('Mensagens')
            ->modalHeading(fn () => 'Mensagens do contato: ' . ($this->bilhetagemModalPhoneRaw ?? $this->bilhetagemModalPhone ?? '-'))
            ->modalWidth(Width::SevenExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(fn () => view('filament.pages.partials.modal-bilhetagem-messages', [
                'phone' => $this->bilhetagemModalPhoneRaw ?? $this->bilhetagemModalPhone,
                'rows' => $this->bilhetagemModalRows,
                'page' => $this->bilhetagemModalPage,
                'perPage' => $this->bilhetagemModalPerPage,
                'total' => $this->bilhetagemModalTotal,
                'lastPage' => $this->bilhetagemModalTotal > 0
                    ? (int) ceil($this->bilhetagemModalTotal / $this->bilhetagemModalPerPage)
                    : 1,
                'contactName' => $this->contactNames[$this->bilhetagemModalPhone] ?? 'Desconhecido',
            ]))
            ->after(fn () => $this->resetBilhetagemModalState());
    }

    public function bilhetagemModalNextPage(): void
    {
        $last = $this->bilhetagemModalTotal > 0
            ? (int) ceil($this->bilhetagemModalTotal / $this->bilhetagemModalPerPage)
            : 1;

        if ($this->bilhetagemModalPage < $last) {
            $this->bilhetagemModalPage++;
            $this->loadBilhetagemModalPage();
        }
    }

    public function bilhetagemModalPrevPage(): void
    {
        if ($this->bilhetagemModalPage > 1) {
            $this->bilhetagemModalPage--;
            $this->loadBilhetagemModalPage();
        }
    }

    protected function loadBilhetagemModalPage(): void
    {
        if (! $this->runId || ! $this->bilhetagemModalPhone) {
            $this->bilhetagemModalRows = [];
            $this->bilhetagemModalTotal = 0;
            return;
        }

        $run = AnaliseRun::find($this->runId);
        if ($run) {
            $this->ensureRunIpsForBilhetagem($run);
            $this->enrichPendingIpsNow($run);
        }

        $this->loadContactNames($this->runId);

        $candidates = array_values(array_unique(array_filter([
            $this->bilhetagemModalPhoneRaw,
            $this->bilhetagemModalPhone,
            '+' . $this->bilhetagemModalPhone,
        ], fn ($v) => is_string($v) && trim($v) !== '')));

        $q = Bilhetagem::query()
            ->where('analise_run_id', $this->runId)
            ->whereIn('recipient', $candidates);

        $this->bilhetagemModalTotal = (int) (clone $q)->count();

        $offset = ($this->bilhetagemModalPage - 1) * $this->bilhetagemModalPerPage;

        $rows = $q->orderByDesc('timestamp_utc')
            ->skip($offset)
            ->take($this->bilhetagemModalPerPage)
            ->get(['timestamp_utc', 'message_id', 'sender_ip', 'sender_port', 'type']);

        $ips = AnaliseRunIp::where('analise_run_id', $this->runId)->pluck('ip')->all();
        $enrs = IpEnrichment::whereIn('ip', $ips)->get()->keyBy('ip');

        // ✅ Brasília
        $tz = 'America/Sao_Paulo';

        $this->bilhetagemModalRows = $rows->map(function (Bilhetagem $b) use ($tz, $enrs) {
            $ipBase = $this->extractIpBase($b->sender_ip);

            $prov = null;
            if ($ipBase && $enrs->has($ipBase)) {
                $en = $enrs->get($ipBase);
                $prov = trim(($en?->isp ?? '') ?: ($en?->org ?? ''));
                $prov = preg_replace('/\s+/u', ' ', $prov ?? '') ?? '';
                if ($prov === '') $prov = null;
            }

            return [
                // ✅ formato BR
                'timestamp' => $b->timestamp_utc ? $b->timestamp_utc->copy()->setTimezone($tz)->format('d/m/Y H:i:s ') : null,
                'sender_ip' => $b->sender_ip,
                'sender_port' => $b->sender_port,
                'sender_provider' => $prov ?: 'Desconhecido',
                'type' => $b->type,
                'message_id' => $b->message_id,
            ];
        })->values()->all();
    }

    protected function resetBilhetagemModalState(): void
    {
        $this->bilhetagemModalPhone = null;
        $this->bilhetagemModalPhoneRaw = null;
        $this->bilhetagemModalPage = 1;
        $this->bilhetagemModalTotal = 0;
        $this->bilhetagemModalRows = [];
    }

    // =========================================================
    // Nome do contato (modal)
    // =========================================================
    public function openContactNameModal(string $phone): void
    {
        $k = $this->normalizePhoneKey($phone);
        if (! $k) {
            Notification::make()->title('Contato inválido')->danger()->send();
            return;
        }

        $this->selectedContactPhone = $k;
        $this->mountAction('contactNameModal');
    }

    public function contactNameModal(): Action
    {
        return Action::make('contactNameModal')
            ->label('Editar nome')
            ->modalHeading('Editar nome do contato')
            ->modalWidth(Width::Large)
            ->modalSubmitActionLabel('Salvar')
            ->modalCancelActionLabel('Cancelar')
            ->form([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(120)
                    ->default(function () {
                        $k = $this->selectedContactPhone;
                        $current = $k ? ($this->contactNames[$k] ?? null) : null;
                        return ($current && $current !== 'Desconhecido') ? $current : '';
                    }),
            ])
            ->action(function (array $data) {
                if (! $this->runId || ! $this->selectedContactPhone) {
                    Notification::make()->title('Run/Contato inválido')->danger()->send();
                    return;
                }

                $name = trim((string) ($data['name'] ?? ''));
                if ($name === '') {
                    Notification::make()->title('Informe um nome válido')->danger()->send();
                    return;
                }

                AnaliseRunContact::updateOrCreate(
                    [
                        'analise_run_id' => $this->runId,
                        'phone' => $this->selectedContactPhone,
                    ],
                    [
                        'name' => $name,
                    ]
                );

                $this->loadContactNames($this->runId);

                $run = AnaliseRun::find($this->runId);
                if ($run) {
                    $this->hydrateReportFromRun($run);
                    $this->tab = 'bilhetagem';
                }

                Notification::make()->title('Nome salvo')->success()->send();
            });
    }

    protected function loadContactNames(int $runId): void
    {
        $this->contactNames = AnaliseRunContact::query()
            ->where('analise_run_id', $runId)
            ->pluck('name', 'phone')
            ->toArray();
    }

    // =========================================================
    // Hydrate report (puxa message_log do banco + garante enrichment)
    // =========================================================
    protected function hydrateReportFromRun(AnaliseRun $run): void
    {
        $parsed = $run->report['_parsed'] ?? null;
        if (! is_array($parsed)) return;

        $this->loadContactNames($run->id);

        // ✅ garante sender_ip em run_ips e tenta enriquecer
        $this->ensureRunIpsForBilhetagem($run);
        $this->enrichPendingIpsNow($run);

        // injeta message_log do banco
        $messageLog = Bilhetagem::query()
            ->where('analise_run_id', $run->id)
            ->orderByDesc('timestamp_utc')
            ->get(['timestamp_utc','message_id','sender','recipient','sender_ip','sender_port','type'])
            ->map(fn (Bilhetagem $b) => [
                'timestamp_utc' => $b->timestamp_utc,
                'message_id' => $b->message_id,
                'sender' => $b->sender,
                'recipient' => $b->recipient,
                'sender_ip' => $b->sender_ip,
                'sender_port' => $b->sender_port,
                'type' => $b->type,
            ])
            ->values()
            ->all();

        $parsed['message_log'] = $messageLog;

        $ips = AnaliseRunIp::where('analise_run_id', $run->id)->pluck('ip')->all();
        $enrs = IpEnrichment::whereIn('ip', $ips)->get()->keyBy('ip');

        $enrichedByIp = [];
        foreach ($ips as $ip) {
            $en = $enrs->get($ip);
            $enrichedByIp[$ip] = [
                'ip' => $ip,
                'city' => $en?->city,
                'isp' => $en?->isp,
                'org' => $en?->org,
                'mobile' => $en?->mobile,
            ];
        }

        $this->report = (new ReportAggregator())->buildReport($parsed, $enrichedByIp);

        // injeta contact_name
        if (isset($this->report['bilhetagem_cards']) && is_array($this->report['bilhetagem_cards'])) {
            foreach ($this->report['bilhetagem_cards'] as &$card) {
                $k = $this->normalizePhoneKey((string) ($card['recipient'] ?? ''));
                $card['contact_name'] = ($k && isset($this->contactNames[$k])) ? $this->contactNames[$k] : 'Desconhecido';
            }
            unset($card);
        }
    }

    // =========================================================
    // loadExistingRun
    // =========================================================
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

        $this->runWarnings = data_get($run->report, '_warnings.ignored_bilhetagens', []) ?: [];

        if ($run->status === 'done') {
            $this->hydrateReportFromRun($run);
            $this->tab = 'timeline';
        }
    }

    // =========================================================
    // ✅ GARANTIR RUN IPS PARA BILHETAGEM + ENRICH
    // =========================================================
    protected function ensureRunIpsForBilhetagem(AnaliseRun $run): int
    {
        $added = 0;

        $senderIps = Bilhetagem::query()
            ->where('analise_run_id', $run->id)
            ->whereNotNull('sender_ip')
            ->pluck('sender_ip')
            ->map(fn ($ip) => $this->extractIpBase(is_string($ip) ? $ip : null))
            ->filter(fn ($ip) => is_string($ip) && trim($ip) !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($senderIps)) {
            return 0;
        }

        $existing = AnaliseRunIp::where('analise_run_id', $run->id)
            ->whereIn('ip', $senderIps)
            ->pluck('ip')
            ->all();

        $map = [];
        foreach ($existing as $ip) $map[$ip] = true;

        foreach ($senderIps as $ip) {
            if (isset($map[$ip])) continue;

            AnaliseRunIp::create([
                'analise_run_id' => $run->id,
                'ip' => $ip,
                'occurrences' => 0,
                'last_seen_at' => null,
                'enriched' => false,
            ]);

            $added++;
        }

        return $added;
    }

    protected function enrichPendingIpsNow(AnaliseRun $run, int $batchSize = 50, int $maxBatches = 6): void
    {
        $pending = AnaliseRunIp::query()
            ->where('analise_run_id', $run->id)
            ->where('enriched', false)
            ->count();

        if ($pending <= 0) return;

        $originalStatus = $run->status;
        $originalProgress = $run->progress;

        if ($run->status !== 'running') {
            $run->status = 'running';
            $run->save();
        }

        for ($i = 0; $i < $maxBatches; $i++) {
            $before = AnaliseRunIp::query()
                ->where('analise_run_id', $run->id)
                ->where('enriched', false)
                ->count();

            if ($before === 0) break;

            app(RunStepper::class)->step($run, $batchSize, 0.0);
            $run->refresh();

            $after = AnaliseRunIp::query()
                ->where('analise_run_id', $run->id)
                ->where('enriched', false)
                ->count();

            if ($after >= $before) break;
        }

        if ($originalStatus !== 'running') {
            $run->refresh();
            $run->status = $originalStatus;
            $run->progress = $originalProgress;
            $run->save();
        }
    }

    // =========================================================
    // Helpers: zip/html + ip + alvo
    // =========================================================
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

                    $lname = strtolower($name);
                    if (str_ends_with($lname, '.html') || str_ends_with($lname, '.htm')) {
                        $htmlContent = $zip->getFromIndex($i);
                        break;
                    }
                }
            }

            $zip->close();

            return is_string($htmlContent) ? $htmlContent : null;
        }

        return $disk->get($storedPath);
    }

    protected function extractIpBase(?string $ipWithPort): ?string
    {
        $ipWithPort = trim((string) $ipWithPort);
        if ($ipWithPort === '') return null;

        if (preg_match('/^\[([0-9a-fA-F:]+)\]:(\d{1,5})$/', $ipWithPort, $m)) return $m[1];
        if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):(\d{1,5})$/', $ipWithPort, $m)) return $m[1];

        return $ipWithPort;
    }

    protected function normalizePhoneKey(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        $digits = trim($digits);
        return $digits !== '' ? $digits : null;
    }

    private function normalizeTarget(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        $digits = trim($digits);

        if ($digits !== '') {
            return strlen($digits) > 10 ? substr($digits, -10) : $digits;
        }

        $v = mb_strtolower($value);
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;
        $v = trim($v);

        return $v !== '' ? $v : null;
    }

    private function targetsMatch(?string $runTargetRaw, ?string $fileTargetRaw): bool
    {
        $a = $this->normalizeTarget($runTargetRaw);
        $b = $this->normalizeTarget($fileTargetRaw);

        if (! $a || ! $b) return false;

        return $a === $b;
    }
}
