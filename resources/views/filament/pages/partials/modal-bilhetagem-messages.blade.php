@php
    $lastPage = $lastPage ?? 1;
    $page = $page ?? 1;
    $total = $total ?? 0;
@endphp

<div class="space-y-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="text-sm text-gray-500">Contato</div>
            <div class="font-semibold">{{ $contactName ?? 'Desconhecido' }}</div>
            <div class="font-mono text-sm text-gray-700 break-all">{{ $phone ?? '-' }}</div>
        </div>

        <div class="text-sm text-gray-500">
            Total: <span class="font-semibold">{{ number_format($total, 0, ',', '.') }}</span>
        </div>
    </div>

    {{-- ✅ Cards --}}
    <div class="grid gap-3">
        @forelse($rows as $r)
            <div class="rounded-xl border p-4">
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">Timestamp (GMT-3)</div>
                        <div class="text-sm font-semibold">{{ $r['timestamp'] ?? '-' }}</div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">IP / Porta</div>
                        <div class="font-mono text-sm break-all">
                            {{ $r['sender_ip'] ?? '-' }}:{{ $r['sender_port'] ?? '-' }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">Provedor (IP)</div>
                        <div class="text-sm font-semibold">{{ $r['sender_provider'] ?? '-' }}</div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">Tipo</div>
                        <div class="text-sm font-semibold">{{ $r['type'] ?? '-' }}</div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs text-gray-500">Message Id</div>
                        <div class="font-mono text-xs break-all">{{ $r['message_id'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-sm text-gray-500">
                Nenhuma mensagem encontrada.
            </div>
        @endforelse
    </div>

    {{-- ✅ Paginação --}}
    <div class="flex items-center justify-between gap-3">
        <div class="text-xs text-gray-500">
            Página {{ $page }} de {{ $lastPage }}
        </div>

        <div class="flex items-center gap-2">
            <x-filament::button
                type="button"
                size="sm"
                color="gray"
                wire:click="bilhetagemModalPrevPage"
                :disabled="$page <= 1"
            >
                Anterior
            </x-filament::button>

            <x-filament::button
                type="button"
                size="sm"
                color="gray"
                wire:click="bilhetagemModalNextPage"
                :disabled="$page >= $lastPage"
            >
                Próxima
            </x-filament::button>
        </div>
    </div>
</div>
