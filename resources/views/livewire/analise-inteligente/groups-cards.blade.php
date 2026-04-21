<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-gray-600">
            Mostrando <span class="font-semibold">{{ count($pagedRows) }}</span> de
            <span class="font-semibold">{{ number_format($total, 0, ',', '.') }}</span> grupos
            (página <span class="font-semibold">{{ $page }}</span> de <span class="font-semibold">{{ $lastPage }}</span>)
        </div>

        <div class="flex items-center gap-2">
            <x-filament::button size="sm" color="gray" wire:click="prevPage" :disabled="$page <= 1">
                Anterior
            </x-filament::button>

            <x-filament::button size="sm" color="gray" wire:click="nextPage" :disabled="$page >= $lastPage">
                Próxima
            </x-filament::button>
        </div>
    </div>

    @if (empty($pagedRows))
        <div class="rounded-xl border p-6 text-center text-sm text-gray-500">
            Nenhum grupo encontrado no relatório.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($pagedRows as $idx => $r)
                <div
                    class="rounded-2xl border bg-white p-4 shadow-sm"
                    wire:key="group-card-{{ $page }}-{{ $idx }}-{{ $r['id'] ?? $idx }}"
                >
                    {{-- ID --}}
                    <div class="text-xs text-gray-500">
                        ID do grupo
                    </div>
                    <div class="mt-1 font-mono text-sm break-all text-gray-900">
                        {{ $r['id'] ?? '-' }}
                    </div>

                    {{-- Nome + participantes --}}
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-xs text-gray-500">
                                Nome do grupo
                            </div>
                            <div class="mt-1 text-lg font-semibold text-gray-900 break-words">
                                {{ $r['assunto'] ?? '-' }}
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            {{-- ✅ Participantes (número centralizado garantido com flex) --}}
                            <div class="rounded-xl border px-3 py-2 w-36">
                                <div class="text-xs text-gray-500 text-center">
                                    Participantes
                                </div>

                                <div class="mt-1 flex items-center justify-center">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ isset($r['membros']) ? number_format((int) $r['membros'], 0, ',', '.') : '-' }}
                                    </div>
                                </div>
                            </div>

                            {{--
                            <div class="rounded-xl border px-3 py-2">
                                <div class="text-xs text-gray-500">Criação (GMT-3)</div>
                                <div class="text-sm font-semibold text-gray-900 whitespace-nowrap">
                                    {{ $r['criacao'] ?? '-' }}
                                </div>
                            </div>
                            --}}
                        </div>
                    </div>

                    {{-- Descrição (colapsável) --}}
                    @if (!empty($r['descricao']))
                        <div class="mt-4">
                            <details class="rounded-xl border p-3">
                                <summary class="cursor-pointer text-sm font-medium text-gray-700">
                                    Ver descrição
                                </summary>
                                <div class="mt-2 text-sm text-gray-900 whitespace-pre-wrap break-words">
                                    {{ $r['descricao'] }}
                                </div>
                            </details>
                        </div>
                    @endif

                    <div class="mt-4 text-xs text-gray-500">
                        Use o <span class="font-mono">ID do grupo</span> para solicitar participantes via ofício.
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
