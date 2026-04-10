<?php

namespace App\Filament\Pages;

use App\Models\AnaliseRun;
use App\Models\AnaliseRunIp;
use App\Models\IpEnrichment;
use App\Services\AnaliseInteligente\Generic\GenericLogParser;
use App\Services\AnaliseInteligente\Generic\GenericReportAggregator;
use App\Services\AnaliseInteligente\RunStepper;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;


class AnaliseInteligenteGenerico extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use HasPageShield;


    protected static ?string $navigationLabel = 'Análise log GENÉRICO';
    protected static ?string $title = 'Análise de log genérico';
    protected static ?string $slug = 'analise-inteligente-generico';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected string $view = 'filament.pages.analise-inteligente-generico';

    public ?array $data = [];
    public ?int $runId = null;
    public int $progress = 0;
    public bool $running = false;
    public ?array $report = null;

    public int $chunkSize = 5;
    public string $tab = 'timeline';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Investigação Telemática';
    }
    public static function getNavigationSort(): ?int
    {
        return 1;
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
                FileUpload::make('log_file')
                    ->label('Arquivo de Log (PDF/TXT/LOG/CSV)')
                    ->required()
                    ->disk('public')
                    ->directory('uploads/generic-logs')
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
        $this->tab = 'timeline';

        $state = $this->form->getState();
        $storedPath = $state['log_file'] ?? null;

        if (is_array($storedPath)) {
            $storedPath = $storedPath[0] ?? null;
        }

        if (! $storedPath || ! Storage::disk('public')->exists($storedPath)) {
            Notification::make()->title('Arquivo não encontrado')->danger()->send();
            return;
        }

        $absPath = Storage::disk('public')->path($storedPath);

        $rawText = $this->extractTextFromFile($absPath, $storedPath);
        if (! is_string($rawText) || trim($rawText) === '') {
            Notification::make()->title('Não foi possível extrair texto do arquivo')->danger()->send();
            return;
        }

        // ✅ sanitiza o texto bruto (remove bytes inválidos antes do parse)
        $rawText = $this->toValidUtf8($rawText, 2_000_000);

        $parsed = (new GenericLogParser())->parse($rawText);

        // ✅ FIX DEFINITIVO: sanitiza recursivamente tudo antes de salvar no JSON
        $parsed = $this->sanitizeForJson($parsed);

        // Mapeia IPs
        $ipsMap = [];
        foreach (($parsed['events'] ?? []) as $event) {
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
            Notification::make()->title('Nenhum IP encontrado no log')->warning()->send();
            return;
        }

        try {
            $run = DB::transaction(function () use ($parsed, $ipsMap, $storedPath) {
                $run = AnaliseRun::create([
                    'uuid' => (string) str()->uuid(),
                    'target' => null,
                    'total_unique_ips' => count($ipsMap),
                    'processed_unique_ips' => 0,
                    'progress' => 0,
                    'status' => 'running',
                    'report' => [
                        '_source' => 'generico',
                        '_file' => $storedPath,
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
        } catch (\Illuminate\Database\Eloquent\JsonEncodingException $e) {
            // Se ainda ocorrer, é algum byte inválido que escapou: avisar com dica
            Notification::make()
                ->title('Erro ao salvar o relatório (JSON/UTF-8)')
                ->body('O texto extraído contém caracteres inválidos. Revise a sanitização ou reduza campos textuais muito longos.')
                ->danger()
                ->send();

            throw $e;
        }

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

        $this->report = (new GenericReportAggregator())->buildReport($parsed, $enrichedByIp);
    }

    private function extractTextFromFile(string $absPath, string $storedPath): string
    {
        $ext = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));

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

        return (string) file_get_contents($absPath);
    }

    /**
     * Sanitiza arrays/strings para JSON do Eloquent (UTF-8 válido).
     * - Remove bytes inválidos via iconv //IGNORE
     * - Trunca strings absurdamente grandes (opcional, mas recomendado)
     */
    private function sanitizeForJson(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->sanitizeForJson($v);
            }
            return $value;
        }

        if (is_string($value)) {
            // limite por string: 50k evita explodir o JSON e reduz risco de lixo de PDF
            return $this->toValidUtf8($value, 50_000);
        }

        // Carbon e outros objetos podem aparecer: deixa passar (Carbon serializa)
        if ($value instanceof \JsonSerializable) {
            return $value;
        }

        return $value;
    }

    private function toValidUtf8(string $value, int $maxLen): string
    {
        // 1) remove bytes inválidos
        $fixed = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if ($fixed === false) {
            // fallback: remove bytes fora do range básico
            $fixed = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $value) ?? $value;
        }

        // 2) garante que não vai estourar storage e evita textos gigantes do PDF
        if ($maxLen > 0 && strlen($fixed) > $maxLen) {
            $fixed = substr($fixed, 0, $maxLen);
        }

        // 3) remove controles invisíveis (agora o texto já é UTF-8 “limpo”)
        $fixed = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $fixed) ?? $fixed;

        return $fixed;
    }
}
