@php
    $names = array_values((array) ($names ?? []));
@endphp

<div class="space-y-3">
    <div class="border-b pb-2">
        <div class="font-semibold text-gray-900">
            {{ $title ?? 'Contas' }}
        </div>
        <div class="text-xs text-gray-500">
            {{ number_format(count($names), 0, ',', '.') }} conta(s) encontrada(s)
        </div>
    </div>

    @if (count($names) === 0)
        <div class="text-sm text-gray-500">
            Nenhum nome encontrado nessa seção do log.
        </div>
    @else
        <div class="max-h-[65vh] overflow-y-auto rounded-xl border divide-y bg-white">
            @foreach ($names as $name)
                <div class="px-4 py-3 text-sm text-gray-900 break-all">
                    {{ $name }}
                </div>
            @endforeach
        </div>
    @endif
</div>
