<x-filament-panels::page>
    <x-filament::section heading="Relatório Processado">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div>
                <div class="text-xs text-gray-500">Run ID</div>
                <div class="font-mono text-sm">{{ $runModel->id }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Status</div>
                <div class="text-sm font-medium">{{ $runModel->status }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Criado em</div>
                <div class="text-sm">{{ $runModel->created_at?->format('d/m/Y H:i:s') }}</div>
            </div>
        </div>
    </x-filament::section>

    @if ($report)
        @php
            $tabs = [
                'timeline' => ['label' => 'Timeline', 'icon' => 'heroicon-o-clock'],
                'unique_ips' => ['label' => 'IPs Únicos', 'icon' => 'heroicon-o-globe-alt'],
                'providers' => ['label' => 'Provedores', 'icon' => 'heroicon-o-building-office-2'],
                'cities' => ['label' => 'Cidades', 'icon' => 'heroicon-o-map-pin'],
                'residencial' => ['label' => 'Residencial (23–06)', 'icon' => 'heroicon-o-home'],
                'movel' => ['label' => 'Móvel', 'icon' => 'heroicon-o-device-phone-mobile'],
            ];

            $counts = [
                'timeline' => count($report['timeline_rows'] ?? []),
                'unique_ips' => count($report['unique_ip_rows'] ?? []),
                'providers' => count($report['provider_stats_rows'] ?? []),
                'cities' => count($report['city_stats_rows'] ?? []),
                'residencial' => count($report['fixed_night_top'] ?? []),
                'movel' => count($report['mobile_top'] ?? []),
            ];
        @endphp

        <x-filament::section class="mt-6" heading="Planilhas">
            <div class="overflow-x-auto">
                <div class="inline-flex gap-2 min-w-max pb-1">
                    @foreach($tabs as $key => $meta)
                        @php $active = $tab === $key; @endphp

                        <x-filament::button
                            type="button"
                            size="sm"
                            :color="$active ? 'primary' : 'gray'"
                            :outlined="! $active"
                            wire:click="$set('tab', '{{ $key }}')"
                            class="whitespace-nowrap"
                        >
                            <span class="inline-flex items-center gap-2">
                                <x-filament::icon :icon="$meta['icon']" class="h-4 w-4" />
                                <span class="font-semibold">{{ $meta['label'] }}</span>

                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $active ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700' }}">
                                    {{ number_format($counts[$key] ?? 0, 0, ',', '.') }}
                                </span>
                            </span>
                        </x-filament::button>
                    @endforeach
                </div>
            </div>
        </x-filament::section>

        <div class="mt-6 space-y-6">
            @if ($tab === 'timeline')
                <x-filament::section heading="Timeline (Eventos)">
                    <livewire:analise-inteligente.timeline-table
                        :rows="$report['timeline_rows'] ?? []"
                        :wire:key="'timeline-view-' . $runModel->id"
                    />
                </x-filament::section>
            @endif

            @if ($tab === 'unique_ips')
                <x-filament::section heading="IPs Únicos (Relevância)">
                    <livewire:analise-inteligente.unique-ips-table
                        :rows="$report['unique_ip_rows'] ?? []"
                        :wire:key="'unique-view-' . $runModel->id"
                    />
                </x-filament::section>
            @endif

            @if ($tab === 'providers')
                <x-filament::section heading="Provedores (Métricas)">
                    <livewire:analise-inteligente.providers-stats-table
                        :rows="$report['provider_stats_rows'] ?? []"
                        :wire:key="'providers-view-' . $runModel->id"
                    />
                </x-filament::section>
            @endif

            @if ($tab === 'cities')
                <x-filament::section heading="Cidades (Concentração)">
                    <livewire:analise-inteligente.cities-stats-table
                        :rows="$report['city_stats_rows'] ?? []"
                        :wire:key="'cities-view-' . $runModel->id"
                    />
                </x-filament::section>
            @endif

            @if ($tab === 'residencial')
                <x-filament::section heading="Residencial (23–06)">
                    @include('filament.pages.partials.sheet-residencial', ['report' => $report])
                </x-filament::section>
            @endif

            @if ($tab === 'movel')
                <x-filament::section heading="Móvel">
                    @include('filament.pages.partials.sheet-movel', ['report' => $report])
                </x-filament::section>
            @endif
        </div>
    @endif

    <x-filament-actions::modals />
    {{ $this->providerIpsModal() }}
</x-filament-panels::page>
