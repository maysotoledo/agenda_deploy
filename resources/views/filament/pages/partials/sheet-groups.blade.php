<div class="space-y-4">
    @if (empty($rows))
        <div class="rounded-xl border p-6 text-center text-sm text-gray-500">
            Nenhum grupo encontrado no relatório.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($rows as $r)
                <div class="rounded-2xl border bg-white p-4 shadow-sm">
                    {{-- ID --}}
                    <div class="text-xs text-gray-500">
                        ID do grupo
                    </div>
                    <div class="mt-1 font-mono text-sm break-all text-gray-900">
                        {{ $r['id'] ?? '-' }}
                    </div>

                    {{-- Nome + participantes + criação --}}
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
                            <div class="rounded-xl border px-3 py-2 text-center">
                                <div class="text-xs text-gray-500">Participantes</div>
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ isset($r['membros']) ? number_format((int) $r['membros'], 0, ',', '.') : '-' }}
                                </div>
                            </div>

                            {{-- <div class="rounded-xl border px-3 py-2">
                                <div class="text-xs text-gray-500">Criação (GMT-3)</div>
                                <div class="text-sm font-semibold text-gray-900 whitespace-nowrap">
                                    {{ $r['criacao'] ?? '-' }}
                                </div>
                            </div> --}}
                        </div>
                    </div>

                    {{-- Descrição --}}
                    @if (!empty($r['descricao']))
                        <div class="mt-4">
                            <div class="text-xs text-gray-500">Descrição</div>
                            <div class="mt-1 text-sm text-gray-900 whitespace-pre-wrap break-words">
                                {{ $r['descricao'] }}
                            </div>
                        </div>
                    @endif


                </div>
            @endforeach
        </div>
    @endif
</div>
