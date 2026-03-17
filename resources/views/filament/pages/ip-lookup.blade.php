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
                    <dt class="text-sm text-gray-500">Cidade</dt>
                    <dd class="text-base font-medium">{{ $result['city'] }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Empresa (ISP/Org)</dt>
                    <dd class="text-base font-medium">{{ $result['company'] }}</dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Tipo de conexão</dt>
                    <dd class="text-base font-medium">{{ $result['connection_type'] }}</dd>
                </div>
            </dl>
        </x-filament::section>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
