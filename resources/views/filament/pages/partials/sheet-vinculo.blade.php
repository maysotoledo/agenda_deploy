@php
    $rows = $rows ?? [];
    $page = max(1, (int) ($this->vinculoPage ?? 1));
    $perPage = max(1, (int) ($this->vinculoPerPage ?? 10));
    $total = count($rows);
    $lastPage = max(1, (int) ceil($total / $perPage));
    $page = min($page, $lastPage);
    $pagedRows = array_slice($rows, ($page - 1) * $perPage, $perPage);
@endphp

<div class="space-y-4">
    @forelse($pagedRows as $row)
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="font-mono text-base font-semibold">{{ $row['ip'] ?? '-' }}</div>
                    <div class="mt-1 text-sm text-gray-600">
                        {{ $row['provider'] ?? '-' }} · {{ $row['city'] ?? '-' }} · {{ $row['type'] ?? '-' }}
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-primary-50 px-2 py-1 font-medium text-primary-700">
                        {{ number_format((int) ($row['targets_count'] ?? 0), 0, ',', '.') }} alvos
                    </span>
                    <span class="rounded-full bg-gray-100 px-2 py-1 font-medium text-gray-700">
                        {{ number_format((int) ($row['total_occurrences'] ?? 0), 0, ',', '.') }} acessos
                    </span>
                    <span class="rounded-full bg-gray-100 px-2 py-1 font-medium text-gray-700">
                        Último: {{ $row['last_seen'] ?? '-' }}
                    </span>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                <table class="w-full min-w-[980px] table-fixed divide-y divide-gray-200 text-sm">
                    <colgroup>
                        <col style="width: 390px;">
                        <col style="width: 140px;">
                        <col style="width: 205px;">
                        <col style="width: 165px;">
                    </colgroup>

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 pr-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Alvo</th>
                            <th class="border-l border-gray-200 py-3 pl-4 pr-6 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Acessos</th>
                            <th class="border-l border-gray-200 px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Primeiro acesso</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Último acesso</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach(($row['accesses'] ?? []) as $access)
                            @php
                                $target = trim((string) ($access['target'] ?? ''));
                                $target = $target !== '' ? $target : 'Alvo não identificado';
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 pr-2 align-middle">
                                    <button
                                        type="button"
                                        class="block max-w-full truncate text-left font-semibold text-primary-600 hover:underline"
                                        title="{{ $target }}"
                                        wire:click="openVinculoTimesModal(@js($row['ip'] ?? ''), @js($target))"
                                    >
                                        {{ $target }}
                                    </button>
                                </td>
                                <td class="border-l border-gray-100 py-3 pl-4 pr-6 text-right tabular-nums align-middle font-medium">
                                    {{ number_format((int) ($access['count'] ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="whitespace-nowrap border-l border-gray-100 px-6 py-3 align-middle font-mono text-xs text-gray-700">
                                    {{ $access['first_seen'] ?? '-' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 align-middle font-mono text-xs text-gray-700">
                                    {{ $access['last_seen'] ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="rounded-lg border bg-white px-4 py-8 text-center text-sm text-gray-500">
            Nenhum IP compartilhado entre alvos diferentes nesta investigação.
        </div>
    @endforelse

    @if($total > $perPage)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-white px-4 py-3 text-sm">
            <div class="text-gray-600">
                Mostrando
                <span class="font-semibold">{{ number_format((($page - 1) * $perPage) + 1, 0, ',', '.') }}</span>
                a
                <span class="font-semibold">{{ number_format(min($page * $perPage, $total), 0, ',', '.') }}</span>
                de
                <span class="font-semibold">{{ number_format($total, 0, ',', '.') }}</span>
                vínculos
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button
                    type="button"
                    size="sm"
                    color="gray"
                    :disabled="$page <= 1"
                    wire:click="setVinculoPage({{ $page - 1 }})"
                >
                    Anterior
                </x-filament::button>

                <span class="px-2 text-gray-600">
                    Página {{ number_format($page, 0, ',', '.') }} de {{ number_format($lastPage, 0, ',', '.') }}
                </span>

                <x-filament::button
                    type="button"
                    size="sm"
                    color="gray"
                    :disabled="$page >= $lastPage"
                    wire:click="setVinculoPage({{ $page + 1 }})"
                >
                    Próxima
                </x-filament::button>
            </div>
        </div>
    @endif
</div>
