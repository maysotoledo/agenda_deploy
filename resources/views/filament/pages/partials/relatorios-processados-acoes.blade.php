@php
    /** @var \App\Models\AnaliseInvestigation $record */
    $record = $getRecord();
    $viewUrl = $this->resolveViewUrl($record);
    $pdfUrl = route('analises.investigacoes.pdf', ['investigation' => $record]);
    $investigationId = $record->id;
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
        tag="a"
        href="{{ $pdfUrl }}"
        size="xs"
        color="gray"
        target="_blank"
    >
        PDF
    </x-filament::button>

    <x-filament::button
        type="button"
        size="xs"
        color="danger"
        wire:click="$dispatch('delete-investigation', { investigationId: {{ $investigationId }} })"
        wire:confirm="Tem certeza que deseja excluir esta investigação?"
    >
        Excluir
    </x-filament::button>
</div>
