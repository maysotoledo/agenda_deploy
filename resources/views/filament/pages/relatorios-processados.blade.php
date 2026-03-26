<x-filament-panels::page>
    @php
        $statusTabs = [
            'done' => ['label' => 'Finalizados', 'icon' => 'heroicon-o-check-circle'],
            'running' => ['label' => 'Em processamento', 'icon' => 'heroicon-o-arrow-path'],
            'error' => ['label' => 'Com erro', 'icon' => 'heroicon-o-exclamation-triangle'],
            'all' => ['label' => 'Todos', 'icon' => 'heroicon-o-squares-2x2'],
        ];
    @endphp

    <x-filament::section heading="Relatórios Processados">
        <div class="overflow-x-auto">
            <div class="inline-flex gap-2 min-w-max pb-1">
                @foreach($statusTabs as $value => $meta)
                    @php $active = $statusFilter === $value; @endphp

                    <x-filament::button
                        type="button"
                        size="sm"
                        :color="$active ? 'primary' : 'gray'"
                        :outlined="! $active"
                        wire:click="$set('statusFilter', '{{ $value }}')"
                        class="whitespace-nowrap"
                    >
                        <span class="inline-flex items-center gap-2">
                            <x-filament::icon :icon="$meta['icon']" class="h-4 w-4" />
                            <span class="font-semibold">{{ $meta['label'] }}</span>
                        </span>
                    </x-filament::button>
                @endforeach
            </div>
        </div>

        <div class="mt-4 overflow-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-2">ID</th>
                        <th class="text-left p-2">Alvo</th>
                        <th class="text-left p-2">Status</th>
                        <th class="text-left p-2">Progresso</th>
                        <th class="text-left p-2">Criado em</th>
                        <th class="text-left p-2">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->runs as $run)
                        <tr class="border-b">
                            <td class="p-2 font-mono">{{ $run->id }}</td>
                            <td class="p-2">{{ $run->target ?? '-' }}</td>
                            <td class="p-2">{{ $run->status }}</td>
                            <td class="p-2">{{ $run->progress }}%</td>
                            <td class="p-2">{{ $run->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="p-2">
                                <div class="flex flex-wrap gap-2">
                                    <x-filament::button
                                        size="sm"
                                        tag="a"
                                        href="{{ \App\Filament\Pages\VerAnaliseRun::getUrl(['run' => $run->id]) }}"
                                    >
                                        Ver
                                    </x-filament::button>

                                    <x-filament::button
                                        size="sm"
                                        color="danger"
                                        wire:click="deleteRun({{ $run->id }})"
                                        wire:confirm="Tem certeza que deseja excluir este relatório?"
                                    >
                                        Excluir
                                    </x-filament::button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-3 text-gray-500">
                                Nenhum relatório encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
