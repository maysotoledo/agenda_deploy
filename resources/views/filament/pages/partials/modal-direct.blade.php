{{-- resources/views/filament/pages/partials/modal-direct.blade.php --}}

@php
    $targetName = trim((string) ($target ?? ''));
    $otherName = trim((string) ($participant ?? ''));

    $isTarget = function (?string $author) use ($targetName): bool {
        $author = trim((string) $author);
        if ($author === '' || $targetName === '') return false;
        return strcasecmp($author, $targetName) === 0;
    };
@endphp

<div class="space-y-3">
    {{-- header simples --}}
    <div class="border-b pb-2">
        <div class="font-semibold text-gray-900">
            {{ $otherName !== '' ? $otherName : 'Direct' }}
        </div>
        <div class="text-xs text-gray-500">
            {{ $targetName !== '' ? "Alvo: {$targetName}" : '' }}
        </div>
    </div>

    @if (empty($messages))
        <div class="text-sm text-gray-500">Sem mensagens encontradas.</div>
    @else
        <div class="max-h-[65vh] overflow-y-auto pr-1 space-y-2 bg-gray-50 rounded-xl p-3 border">
            @foreach ($messages as $m)
                @php
                    $author = trim((string) ($m['author'] ?? ''));
                    $dt = (string) ($m['datetime'] ?? '—');
                    $body = (string) ($m['body'] ?? '—');

                    $fromTarget = $isTarget($author);

                    $rowAlign = $fromTarget ? 'justify-end' : 'justify-start';

                    // bolha
                    $bubbleClass = $fromTarget
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-200 text-gray-900';

                    // data dentro (cor mais suave)
                    $dtClass = $fromTarget
                        ? 'text-white/80'
                        : 'text-gray-500';

                    // “corte” no canto igual chat
                    $shape = $fromTarget
                        ? 'rounded-2xl rounded-br-md'
                        : 'rounded-2xl rounded-bl-md';
                @endphp

                <div class="flex {{ $rowAlign }}">
                    <div class="max-w-[78%]">
                        <div class="px-3 py-2 {{ $bubbleClass }} {{ $shape }}">
                            {{-- texto --}}
                            <div class="text-sm leading-relaxed whitespace-pre-wrap break-words">
                                {{ $body }}
                            </div>

                            {{-- data no canto, dentro da bolha --}}
                            <div class="mt-1 text-[11px] {{ $dtClass }} flex justify-end">
                                {{ $dt }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
