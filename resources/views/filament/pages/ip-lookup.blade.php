<x-filament-panels::page>
    <form wire:submit="lookup" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            Consultar
        </x-filament::button>
    </form>

    @if ($result)
        <x-filament::section class="mt-6" heading="Resultado">
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <dt class="text-sm text-gray-500"><b>Cidade:</b> {{ $result['city'] }}</dt>
                </div>

                <div>
                    <dt class="text-sm text-gray-500"><b>Empresa:</b> {{ $result['company'] }}</dt>
                </div>

                <div>
                    <dt class="text-sm text-gray-500"><b>Tipo de conexão:</b> {{ $result['connection_type'] }}</dt>
                </div>
            </dl>
        </x-filament::section>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
