@php
    $cards = $cards ?? [];
@endphp

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($cards as $c)
        @php
            $recipient = $c['recipient'] ?? '-';
            $inAgenda = (bool)($c['in_agenda'] ?? false);
            $latest = $c['latest'] ?? null;
            $others = $c['others'] ?? [];
            $total = (int)($c['total'] ?? 0);
        @endphp

        <div class="rounded-xl border p-4" x-data="{ open: false }">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs text-gray-500">Recipients</div>

                    <div class="mt-1 flex items-center gap-2">
                        <div class="font-mono text-sm break-all">{{ $recipient }}</div>

                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $inAgenda ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $inAgenda ? 'Na agenda' : 'Fora da agenda' }}
                        </span>
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        Total mensagens: <span class="font-semibold">{{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                </div>

                @if(count($others) > 0)
                    <button
                        type="button"
                        class="text-sm font-semibold text-primary-600 hover:underline whitespace-nowrap"
                        @click="open = !open"
                    >
                        <span x-show="!open">Ver mais</span>
                        <span x-show="open">Ocultar</span>
                    </button>
                @endif
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
                            <div class="text-xs text-gray-500">Tipo</div>
                            <div class="text-sm font-semibold">{{ data_get($latest, 'type', '-') }}</div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div class="text-xs text-gray-500">Message Id</div>
                            <div class="font-mono text-xs break-all">{{ data_get($latest, 'message_id', '-') }}</div>
                        </div>
                    </div>
                </div>

                @if(count($others) > 0)
                    <div x-show="open" x-transition class="mt-3 space-y-2">
                        @foreach($others as $o)
                            <div class="rounded-lg border p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-xs text-gray-500">Timestamp</div>
                                    <div class="text-sm font-semibold">{{ data_get($o, 'timestamp', '-') }}</div>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <div class="text-xs text-gray-500">IP / Porta</div>
                                    <div class="font-mono text-sm break-all">
                                        {{ data_get($o, 'sender_ip', '-') }}:{{ data_get($o, 'sender_port', '-') }}
                                    </div>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <div class="text-xs text-gray-500">Tipo</div>
                                    <div class="text-sm font-semibold">{{ data_get($o, 'type', '-') }}</div>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <div class="text-xs text-gray-500">Message Id</div>
                                    <div class="font-mono text-xs break-all">{{ data_get($o, 'message_id', '-') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="text-sm text-gray-500">
            Nenhuma bilhetagem encontrada nos arquivos enviados.
        </div>
    @endforelse
</div>
