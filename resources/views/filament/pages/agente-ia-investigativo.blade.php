<x-filament-panels::page>
    <div class="space-y-6">

        <x-filament::section>
            <x-slot name="heading">
                Selecionar modelo
            </x-slot>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        Modelo de IA local
                    </label>

                    <select
                        wire:model="modeloIa"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                        @foreach ($this->getModelosDisponiveis() as $modelo => $descricao)
                            <option value="{{ $modelo }}">
                                {{ $descricao }} — {{ $modelo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="text-sm text-gray-500 dark:text-gray-400">
                    O modelo selecionado precisa estar instalado no Ollama.
                    Exemplo: <code>ollama pull llama3.2:3b</code>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Selecionar relatório
            </x-slot>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        Relatório processado
                    </label>

                    <select
                        wire:model="analise_run_id"
                        class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                        <option value="">Selecione um relatório...</option>

                        @foreach ($this->getRelatoriosDisponiveis() as $run)
                            <option value="{{ $run->id }}">
                                #{{ $run->id }}
                                —
                                {{ $run->target ?? 'Sem alvo' }}
                                —
                                {{ optional($run->created_at)->format('d/m/Y H:i') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Usuários comuns visualizam apenas relatórios criados por eles.
                    O super_admin pode selecionar qualquer relatório processado.
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Pergunta livre ao agente
            </x-slot>

            <div class="space-y-4">
                <textarea
                    wire:model="perguntaLivre"
                    rows="4"
                    class="block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    placeholder="Pergunte ao agente sobre este relatório..."
                ></textarea>

                <x-filament::button
                    wire:click="gerarAnalise('pergunta_livre')"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-paper-airplane"
                >
                    Perguntar ao agente
                </x-filament::button>

                <div wire:loading wire:target="gerarAnalise" class="text-sm text-gray-500 dark:text-gray-400">
                    Gerando análise com IA local. Aguarde...
                </div>
            </div>
        </x-filament::section>

        @if ($ultimaResposta)
            <x-filament::section>
                <x-slot name="heading">
                    Última resposta gerada
                </x-slot>

                <x-slot name="description">
                    Tipo: {{ $ultimoTipo }} | Modelo: {{ $modeloIa }}
                </x-slot>

                <div class="prose dark:prose-invert max-w-none whitespace-pre-wrap">
                    {{ $ultimaResposta }}
                </div>
            </x-filament::section>
        @endif

        <x-filament::section>
            <x-slot name="heading">
                Orientação de uso
            </x-slot>

            <div class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
                <p>
                    Este agente utiliza IA local via Ollama. A análise gerada é apenas apoio técnico
                    e deve ser validada pelo investigador.
                </p>

                <p>
                    A IA não deve ser usada para afirmar autoria, culpa ou conclusão definitiva.
                    Ela deve apontar padrões, indícios, limitações e diligências possíveis.
                </p>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
