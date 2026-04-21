@php
    $cards = $cards ?? [];
@endphp

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($cards as $c)
        @php
            $phone = $c['recipient'] ?? '-';
            $contactName = $c['contact_name'] ?? 'Desconhecido';
            $inAgenda = (bool)($c['in_agenda'] ?? false);
            $latest = $c['latest'] ?? null;
            $total = (int)($c['total'] ?? 0);
        @endphp

        <div class="rounded-xl border p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 w-full">
                    <div class="flex items-center gap-2">
                        <div class="text-xs text-gray-500">Contato</div>

                        @if($inAgenda)
                            <x-filament::badge color="success">
                                <span class="inline-flex items-center">
                                    <span class="inline-flex h-5 w-5 items-center justify-center">
                                        <x-filament::icon icon="heroicon-m-phone" class="h-4 w-4" />
                                    </span>
                                    <span class="ml-1">Na agenda</span>
                                </span>
                            </x-filament::badge>
                        @else
                            <x-filament::badge color="danger">
                                <span class="inline-flex items-center">
                                    <span class="inline-flex h-5 w-5 items-center justify-center">
                                        <x-filament::icon icon="heroicon-m-phone-x-mark" class="h-4 w-4" />
                                    </span>
                                    <span class="ml-1">Fora da agenda</span>
                                </span>
                            </x-filament::badge>
                        @endif
                    </div>

                    <div class="mt-1">
                        <button
                            type="button"
                            wire:click="openContactNameModal('{{ $phone }}')"
                            class="text-left min-w-0"
                            title="Clique para editar o nome"
                        >
                            <div class="font-semibold truncate max-w-[260px] hover:underline">
                                {{ $contactName ?: 'Desconhecido' }}
                            </div>
                        </button>
                    </div>

                    <div class="mt-1 font-mono text-sm break-all text-gray-700">
                        {{ $phone }}
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">
                            Total mensagens: <span class="font-semibold">{{ number_format($total, 0, ',', '.') }}</span>
                        </div>

                        <x-filament::button
                            type="button"
                            size="sm"
                            color="gray"
                            wire:click="openBilhetagemMessagesModal('{{ $phone }}')"
                        >
                            Ver mensagens
                        </x-filament::button>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="rounded-lg border p-3">
                    <div class="text-xs text-gray-500">Última mensagem</div>

                    <div class="mt-2 grid gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-xs text-gray-500">Timestamp (GMT-3)</div>
                            <div class="text-sm font-semibold">{{ data_get($latest, 'timestamp', '-') }}</div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div class="text-xs text-gray-500">IP / Porta</div>
                            <div class="font-mono text-sm break-all">
                                {{ data_get($latest, 'sender_ip', '-') }}:{{ data_get($latest, 'sender_port', '-') }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div class="text-xs text-gray-500">Provedor (IP)</div>
                            <div class="text-sm font-semibold">{{ data_get($latest, 'sender_provider', '-') }}</div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div class="text-xs text-gray-500">Tipo</div>
                            <div class="text-sm font-semibold">{{ data_get($latest, 'type', '-') }}</div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div class="text-xs text-gray-500">Message Id</div>
                            <div class="font-mono text-xs break-all">{{ data_get($latest, 'message_id', '-') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-sm text-gray-500">
            Nenhuma bilhetagem encontrada nos arquivos enviados.
        </div>
    @endforelse
</div>
