<x-filament-panels::page>
    <form wire:submit="gerar" class="space-y-6" wire:loading.class="opacity-75" wire:target="gerar">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="gerar" :disabled="$running">
                <span wire:loading.remove wire:target="gerar">
                    {{ $running ? 'Processando...' : 'Gerar relatório' }}
                </span>
                <span wire:loading wire:target="gerar">Gerando...</span>
            </x-filament::button>

            <x-filament::button type="button" color="gray" wire:click="limpar" wire:loading.attr="disabled" wire:target="limpar,gerar" :disabled="$running">
                Limpar
            </x-filament::button>
        </div>
    </form>

    @if ($runId)
        <x-filament::section class="mt-6" heading="Progresso">
            <div wire:poll.1000ms="poll" class="space-y-3">
                <div class="text-sm text-gray-500">
                    Run ID: <span class="font-mono">{{ $runId }}</span>
                </div>

                <div class="w-full bg-gray-200 rounded h-3 overflow-hidden">
                    <div class="bg-primary-600 h-3" style="width: {{ $progress }}%"></div>
                </div>

                <div class="text-sm">
                    {{ $progress }}% @if($running) (processando...) @else (finalizado) @endif
                </div>
            </div>
        </x-filament::section>
    @endif

    @if ($report)
        <x-filament::section class="mt-6" heading="Resumo da Análise">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Período (GMT-3)</div>
                    <div class="font-semibold">{{ $report['period_label'] ?? '-' }}</div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">Total de eventos</div>
                    <div class="font-semibold">{{ number_format($report['total_events'] ?? 0, 0, ',', '.') }}</div>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="text-sm text-gray-500">IPs únicos</div>
                    <div class="font-semibold">{{ number_format($report['total_unique_ips'] ?? 0, 0, ',', '.') }}</div>
                </div>

                <div class="rounded-xl border p-4 md:col-span-2 xl:col-span-3">
                    <div class="text-sm text-gray-500">Emails encontrados</div>
                    <div class="font-semibold break-all">
                        @if(!empty($report['emails_found']))
                            {{ implode(', ', $report['emails_found']) }}
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border p-4 md:col-span-2 xl:col-span-3">
                    <div class="text-sm text-gray-500">Achados sugestivos</div>
                    <ul class="mt-2 list-disc pl-5 space-y-1 text-sm text-gray-700">
                        @forelse(($report['investigation_hints'] ?? []) as $hint)
                            <li>{{ $hint }}</li>
                        @empty
                            <li>Nenhum achado automático.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </x-filament::section>

        @php
            $tabs = [
                'timeline' => ['label' => 'Timeline', 'icon' => 'heroicon-o-clock'],
                'unique_ips' => ['label' => 'IPs Únicos', 'icon' => 'heroicon-o-globe-alt'],
                'providers' => ['label' => 'Provedores', 'icon' => 'heroicon-o-building-office-2'],
                'cities' => ['label' => 'Cidades', 'icon' => 'heroicon-o-map-pin'],
                'residencial' => ['label' => 'Noturno (23–06)', 'icon' => 'heroicon-o-moon'],
                'movel' => ['label' => 'Móvel', 'icon' => 'heroicon-o-device-phone-mobile'],
            ];

            $counts = [
                'timeline' => count($report['timeline_rows'] ?? []),
                'unique_ips' => count($report['unique_ip_rows'] ?? []),
                'providers' => count($report['provider_stats_rows'] ?? []),
                'cities' => count($report['city_stats_rows'] ?? []),
                'residencial' => (int) ($report['night_total_events'] ?? 0),
                'movel' => (int) ($report['mobile_total_events'] ?? 0),
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
                    <livewire:analise-inteligente.generic-timeline-table
                        :rows="$report['timeline_rows'] ?? []"
                        :wire:key="'gen-timeline-' . $runId"
                    />
                </x-filament::section>
            @endif

            @if ($tab === 'unique_ips')
                <x-filament::section heading="IPs Únicos (Relevância)">
                    <livewire:analise-inteligente.generic-unique-ips-table
                        :rows="$report['unique_ip_rows'] ?? []"
                        :wire:key="'gen-unique-' . $runId"
                    />
                </x-filament::section>
            @endif

            @if ($tab === 'providers')
                <x-filament::section heading="Provedores (Métricas)">
                    <livewire:analise-inteligente.generic-providers-table
                        :rows="$report['provider_stats_rows'] ?? []"
                        :wire:key="'gen-providers-' . $runId"
                    />
                </x-filament::section>
            @endif

            @if ($tab === 'cities')
                <x-filament::section heading="Cidades (Concentração)">
                    <livewire:analise-inteligente.generic-cities-table
                        :rows="$report['city_stats_rows'] ?? []"
                        :wire:key="'gen-cities-' . $runId"
                    />
                </x-filament::section>
            @endif

            @if ($tab === 'residencial')
                <x-filament::section heading="Noturno (23–06)">
                    <livewire:analise-inteligente.generic-timeline-table
                        :rows="$report['night_events_rows'] ?? []"
                        :wire:key="'gen-night-' . $runId"
                    />
                </x-filament::section>
            @endif

            @if ($tab === 'movel')
                <x-filament::section heading="Móvel">
                    <livewire:analise-inteligente.generic-timeline-table
                        :rows="$report['mobile_events_rows'] ?? []"
                        :wire:key="'gen-mobile-' . $runId"
                    />
                </x-filament::section>
            @endif
        </div>
    @endif
</x-filament-panels::page>
