@php
    /** @var \App\Models\AnaliseInvestigation $record */
    $record = $getRecord();
    $viewUrl = $this->resolveViewUrl($record);
    $investigationId = $record->id;
@endphp

<div class="flex items-center gap-2">
    <x-filament::button
        tag="a"
        href="{{ $viewUrl }}"
        size="xs"
        color="primary"
    >
        Ver relatório
    </x-filament::button>

    <x-filament::button
        type="button"
        size="xs"
        color="danger"
        wire:click="$dispatch('delete-investigation', { investigationId: {{ $investigationId }} })"
        wire:confirm="Tem certeza que deseja excluir esta investigação e todos os alvos vinculados?"
    >
        Excluir
    </x-filament::button>
</div>
