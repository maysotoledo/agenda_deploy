<?php

namespace App\Filament\Pages\Concerns;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use App\Services\AnaliseInteligente\Platform\PlatformLogParser;
use App\Services\AnaliseInteligente\Platform\PlatformReportAggregator;
use App\Services\AnaliseInteligente\RunStepper;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait HandlesPlatformLogAnalysis
{
    public ?array $data = [];
    public ?int $runId = null;
    public int $progress = 0;
    public bool $running = false;
    public ?array $report = null;
    public int $chunkSize = 8;
    public string $tab = 'timeline';

    abstract protected function platformSource(): string;
    abstract protected function platformLabel(): string;

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
                FileUpload::make('log_files')
                    ->label('Arquivos de log ' . $this->platformLabel() . ' (PDF/TXT/LOG/CSV/JSON/HTML/ZIP)')
                    ->required()
                    ->multiple()
                    ->disk('public')
                    ->directory('uploads/' . $this->platformSource() . '-logs')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'text/plain',
                        'text/csv',
                        'text/html',
                        'application/json',
                        'application/zip',
                        'application/x-zip-compressed',
                        '.pdf',
                        '.txt',
                        '.log',
                        '.csv',
                        '.json',
                        '.html',
                        '.htm',
                        '.zip',
                    ])
                    ->preserveFilenames()
                    ->maxSize(150_000),
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
        $this->running = false;
        $this->tab = 'timeline';

        $state = $this->form->getState();
        $storedPaths = $state['log_files'] ?? [];

        if (is_string($storedPaths)) {
            $storedPaths = [$storedPaths];
        }

        if (! is_array($storedPaths) || count($storedPaths) === 0) {
            Notification::make()->title('Envie pelo menos 1 arquivo de log')->danger()->send();
            return;
        }

        $rawText = $this->extractTextFromUploads($storedPaths);
        if (trim($rawText) === '') {
            Notification::make()->title('Não foi possível extrair texto dos arquivos')->danger()->send();
            return;
        }

        $rawText = $this->toValidUtf8($rawText, 3_000_000);
        $parsed = (new PlatformLogParser($this->platformSource(), $this->platformLabel()))->parse($rawText);
        $parsed = $this->sanitizeForJson($parsed);

        $ipsMap = $this->buildIpsMap($parsed['events'] ?? []);

        if (count($ipsMap) === 0) {
            Notification::make()->title('Nenhum IP com data/hora foi encontrado no log')->warning()->send();
            return;
        }

        $target = $this->resolveTarget($parsed);

        $run = DB::transaction(function () use ($parsed, $ipsMap, $storedPaths, $target) {
            $run = AnaliseRun::create([
                'user_id' => auth()->id(),
                'uuid' => (string) str()->uuid(),
                'target' => $target,
                'total_unique_ips' => count($ipsMap),
                'processed_unique_ips' => 0,
                'progress' => 0,
                'status' => 'running',
                'report' => [
                    '_source' => $this->platformSource(),
                    '_files' => array_values($storedPaths),
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
            app(RunStepper::class)->step($run, $this->chunkSize, 0.5);

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

        $this->form->fill();
    }

    protected function loadExistingRun(int $runId): void
    {
        $run = AnaliseRun::find($runId);

        if (! $run) {
            Notification::make()->title('Relatório processado não encontrado')->danger()->send();
            return;
        }

        $source = strtolower((string) ($run->report['_source'] ?? ''));
        if ($source !== $this->platformSource()) {
            Notification::make()->title('Este relatório pertence a outro tipo de análise')->danger()->send();
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

        $this->report = (new PlatformReportAggregator())->buildReport($parsed, $enrichedByIp);
    }

    private function extractTextFromUploads(array $storedPaths): string
    {
        $chunks = [];
        $disk = Storage::disk('public');

        foreach ($storedPaths as $storedPath) {
            if (! is_string($storedPath) || ! $disk->exists($storedPath)) {
                continue;
            }

            $fullPath = $disk->path($storedPath);
            $text = $this->extractTextFromFile($fullPath, $storedPath);

            if (trim($text) !== '') {
                $chunks[] = "\n\n===== {$storedPath} =====\n\n" . $text;
            }
        }

        return implode("\n\n", $chunks);
    }

    private function extractTextFromFile(string $absPath, string $storedPath): string
    {
        $ext = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));

        if ($ext === 'zip') {
            return $this->extractTextFromZip($absPath);
        }

        if ($ext === 'pdf') {
            try {
                if (class_exists(\Smalot\PdfParser\Parser::class)) {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($absPath);
                    return (string) $pdf->getText();
                }
            } catch (\Throwable) {
                return '';
            }

            return '';
        }

        return is_file($absPath) ? (string) file_get_contents($absPath) : '';
    }

    private function extractTextFromZip(string $absPath): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($absPath) !== true) {
            return '';
        }

        $chunks = [];
        $allowed = ['txt', 'log', 'csv', 'json', 'html', 'htm', 'xml'];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name)) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (! in_array($ext, $allowed, true)) {
                continue;
            }

            $content = $zip->getFromIndex($i);
            if (is_string($content) && trim($content) !== '') {
                $chunks[] = "\n\n===== {$name} =====\n\n" . $content;
            }
        }

        $zip->close();

        return implode("\n\n", $chunks);
    }

    private function buildIpsMap(array $events): array
    {
        $ipsMap = [];

        foreach ($events as $event) {
            $ip = trim((string) ($event['ip'] ?? ''));
            if ($ip === '') {
                continue;
            }

            $time = $event['time_utc'] ?? null;
            $ts = null;

            if ($time instanceof Carbon) {
                $ts = $time->timestamp;
            } elseif (is_string($time) && trim($time) !== '') {
                $ts = strtotime($time) ?: null;
            } elseif (is_int($time)) {
                $ts = $time;
            }

            $ipsMap[$ip] ??= ['occurrences' => 0, 'last_seen_ts' => $ts];
            $ipsMap[$ip]['occurrences']++;

            if ($ts && ($ipsMap[$ip]['last_seen_ts'] === null || $ts > $ipsMap[$ip]['last_seen_ts'])) {
                $ipsMap[$ip]['last_seen_ts'] = $ts;
            }
        }

        return $ipsMap;
    }

    private function resolveTarget(array $parsed): ?string
    {
        $emails = (array) ($parsed['emails'] ?? []);
        if (count($emails) > 0) {
            return (string) $emails[0];
        }

        $identifiers = (array) ($parsed['identifiers'] ?? []);
        $first = $identifiers[0]['value'] ?? null;

        return is_string($first) && trim($first) !== '' ? trim($first) : null;
    }

    private function sanitizeForJson(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $child) {
                $value[$key] = $this->sanitizeForJson($child);
            }

            return $value;
        }

        if (is_string($value)) {
            return $this->toValidUtf8($value, 80_000);
        }

        return $value;
    }

    private function toValidUtf8(string $value, int $maxLen): string
    {
        $fixed = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if ($fixed === false) {
            $fixed = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value) ?? $value;
        }

        if ($maxLen > 0 && strlen($fixed) > $maxLen) {
            $fixed = substr($fixed, 0, $maxLen);
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $fixed) ?? $fixed;
    }
}
