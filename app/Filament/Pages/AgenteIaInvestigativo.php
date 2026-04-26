<?php

namespace App\Filament\Pages;

use App\Models\AiAnalysis;
use App\Models\AnaliseRun;
use App\Services\IA\OllamaService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AgenteIaInvestigativo extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Investigação Telemática';

    protected static ?string $navigationLabel = 'Agente IA';

    protected static ?string $title = 'Agente IA Investigativo';

    protected string $view = 'filament.pages.agente-ia-investigativo';

    public ?int $analise_run_id = null;

    public string $modeloIa = '';

    public string $perguntaLivre = '';

    public ?string $ultimaResposta = null;

    public ?string $ultimoTipo = null;

    public function mount(): void
    {
        $this->modeloIa = config('services.ollama.model', 'llama3.2:3b');
    }

    public function getModelosDisponiveis(): array
    {
        return [
            'llama3.2:3b' => 'Llama 3.2 3B — texto geral leve',
            'qwen2.5:3b' => 'Qwen 2.5 3B — análise/raciocínio leve',
            'qwen2.5-coder:3b' => 'Qwen Coder 3B — código e estrutura técnica',
            'llama3.1:8b' => 'Llama 3.1 8B — melhor texto, mais pesado',
            'qwen2.5:7b' => 'Qwen 2.5 7B — melhor análise, mais pesado',
        ];
    }

    public function getRelatoriosDisponiveis(): Collection
    {
        return AnaliseRun::query()
            ->when(
                ! Auth::user()?->hasRole('super_admin'),
                fn ($query) => $query->where('user_id', Auth::id())
            )
            ->latest()
            ->limit(100)
            ->get();
    }

    public function gerarAnalise(string $tipo): void
    {
        if (!$this->modeloIa) {
            Notification::make()
                ->title('Selecione um modelo de IA primeiro.')
                ->warning()
                ->send();

            return;
        }

        if (!$this->analise_run_id) {
            Notification::make()
                ->title('Selecione um relatório primeiro.')
                ->warning()
                ->send();

            return;
        }

        $run = AnaliseRun::query()
            ->when(
                ! Auth::user()?->hasRole('super_admin'),
                fn ($query) => $query->where('user_id', Auth::id())
            )
            ->find($this->analise_run_id);

        if (!$run) {
            Notification::make()
                ->title('Relatório não encontrado ou sem permissão de acesso.')
                ->danger()
                ->send();

            return;
        }

        if ($tipo === 'pergunta_livre' && trim($this->perguntaLivre) === '') {
            Notification::make()
                ->title('Digite uma pergunta para o agente.')
                ->warning()
                ->send();

            return;
        }

        $pergunta = $tipo === 'pergunta_livre'
            ? trim($this->perguntaLivre)
            : $this->montarPerguntaPorTipo($tipo);

        $contexto = $this->montarContextoRelatorio($run);

        $resposta = app(OllamaService::class)->chat(
            pergunta: $pergunta,
            contexto: $contexto,
            tipo: $tipo,
            modelo: $this->modeloIa
        );

        AiAnalysis::query()->create([
            'analise_run_id' => $run->id,
            'user_id' => Auth::id(),
            'tipo' => $tipo,
            'modelo' => $this->modeloIa,
            'pergunta' => $pergunta,
            'contexto' => $contexto,
            'resposta' => $resposta,
        ]);

        $this->ultimaResposta = $resposta;
        $this->ultimoTipo = $tipo;

        Notification::make()
            ->title('Análise gerada e salva com sucesso.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resumo_tecnico')
                ->label('Gerar resumo técnico')
                ->icon(Heroicon::OutlinedDocumentText)
                ->action(fn () => $this->gerarAnalise('resumo_tecnico')),

            Action::make('linha_investigacao')
                ->label('Gerar linha de investigação')
                ->icon(Heroicon::OutlinedMap)
                ->action(fn () => $this->gerarAnalise('linha_investigacao')),

            Action::make('relatorio_policial')
                ->label('Gerar minuta de relatório')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->action(fn () => $this->gerarAnalise('relatorio_policial')),

            Action::make('analise_noturna')
                ->label('Analisar acessos noturnos')
                ->icon(Heroicon::OutlinedMoon)
                ->action(fn () => $this->gerarAnalise('analise_noturna')),

            Action::make('analise_ips_moveis')
                ->label('Analisar IPs móveis')
                ->icon(Heroicon::OutlinedDevicePhoneMobile)
                ->action(fn () => $this->gerarAnalise('analise_ips_moveis')),
        ];
    }

    private function montarPerguntaPorTipo(string $tipo): string
    {
        return match ($tipo) {
            'resumo_tecnico' => 'Gere um resumo técnico objetivo da análise telemática, destacando dados objetivos, padrões observados, pontos relevantes e limitações da análise.',

            'linha_investigacao' => 'Sugira uma linha de investigação com base nos dados fornecidos, indicando possíveis diligências, cruzamentos úteis e pontos que exigem validação humana.',

            'relatorio_policial' => 'Gere uma minuta formal de relatório policial de análise telemática, com linguagem técnica, objetiva, sem afirmar autoria, culpa ou conclusão definitiva.',

            'analise_noturna' => 'Analise os acessos noturnos, destacando horários incomuns, recorrência, possíveis padrões e necessidade de validação.',

            'analise_ips_moveis' => 'Analise especificamente os IPs móveis, provedores móveis, eventos classificados como mobile, recorrência, horários e possíveis padrões relevantes. Não invente dados ausentes.',

            default => 'Analise os dados fornecidos de forma técnica, objetiva e cautelosa.',
        };
    }

    private function montarContextoRelatorio(AnaliseRun $run): array
    {
        $report = is_array($run->report) ? $run->report : [];

        return [
            'id_relatorio' => $run->id,
            'uuid' => $run->uuid ?? null,
            'alvo' => $run->target ?? null,
            'status' => $run->status ?? null,
            'total_unique_ips' => $run->total_unique_ips ?? null,
            'processed_unique_ips' => $run->processed_unique_ips ?? null,
            'created_at' => optional($run->created_at)->format('d/m/Y H:i:s'),
            'updated_at' => optional($run->updated_at)->format('d/m/Y H:i:s'),
            'report' => $this->limitarContexto($report),
        ];
    }

    private function limitarContexto(array $report): array
    {
        return [
            'summary' => $report['summary'] ?? $report['resumo'] ?? null,
            'target' => $report['target'] ?? null,
            'period' => $report['period'] ?? $report['periodo'] ?? null,
            'device' => $report['device'] ?? $report['dispositivo'] ?? null,

            'providers' => array_slice(
                $report['providers'] ?? $report['provedores'] ?? [],
                0,
                30
            ),

            'timeline' => array_slice(
                $report['timeline'] ?? $report['linha_do_tempo'] ?? [],
                0,
                80
            ),

            'night_events' => array_slice(
                $report['night_events'] ?? $report['acessos_noturnos'] ?? [],
                0,
                80
            ),

            'mobile_events' => array_slice(
                $report['mobile_events'] ?? $report['eventos_moveis'] ?? [],
                0,
                80
            ),

            'contacts' => [
                'symmetric' => array_slice(
                    $report['contacts']['symmetric'] ?? $report['contatos_simetricos'] ?? [],
                    0,
                    80
                ),

                'asymmetric' => array_slice(
                    $report['contacts']['asymmetric'] ?? $report['contatos_assimetricos'] ?? [],
                    0,
                    80
                ),
            ],

            'billing' => array_slice(
                $report['billing'] ?? $report['bilhetagem'] ?? [],
                0,
                100
            ),

            'locations' => array_slice(
                $report['locations'] ?? $report['localizacoes'] ?? [],
                0,
                50
            ),
        ];
    }
}
