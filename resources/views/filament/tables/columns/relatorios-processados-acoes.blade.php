@php
    $runId = $getRecord()->id;
@endphp

<div class="flex items-center gap-2">
    <x-filament::button
        type="button"
        size="xs"
        color="primary"
        wire:click="$dispatch('view-run', { runId: {{ $runId }} })"
    >
        Ver
    </x-filament::button>

    <x-filament::button
        type="button"
        size="xs"
        color="danger"
        wire:click="$dispatch('delete-run', { runId: {{ $runId }} })"
        wire:confirm="Tem certeza que deseja excluir este relatório?"
    >
        Excluir
    </x-filament::button>
</div>
