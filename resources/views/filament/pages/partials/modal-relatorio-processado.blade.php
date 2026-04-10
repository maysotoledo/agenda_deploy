@php
    /** @var \App\Models\AnaliseRun $record */
    $record = $getRecord();
    $page = app(\App\Filament\Pages\RelatoriosProcessados::class);
    $viewUrl = $page->resolveViewUrl($record);
    $runId = $record->id;
@endphp

<div class="flex items-center gap-2">
    <x-filament::button
        tag="a"
        href="{{ $viewUrl }}"
        size="xs"
        color="primary"
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
