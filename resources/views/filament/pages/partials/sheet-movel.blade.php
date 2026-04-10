<div class="space-y-6">

    <div>
        <div class="text-sm text-gray-500 mb-2">
            Total de eventos móveis:
            <span class="font-semibold">
                {{ number_format($report['mobile_total_events'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
    </div>

    <div>
        <div class="text-sm text-gray-500 mb-2">
        </div>

        <livewire:analise-inteligente.mobile-events-table
            :rows="collect($report['mobile_events_rows'] ?? [])->map(fn ($r) => [
                'ip' => $r['ip'] ?? '-',
                'datetime' => $r['datetime'] ?? '-',
                'provider' => $r['provider'] ?? '-',
                'city' => $r['city'] ?? '-',
                'type' => 'Móvel',
            ])->values()->all()"
        />
    </div>

</div>
