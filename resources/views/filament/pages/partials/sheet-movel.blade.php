<div class="space-y-6">
    <div>
        <div class="text-sm text-gray-500 mb-2">Ranking - conexões móveis</div>

        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-2 w-12">#</th>
                        <th class="text-left p-2">Provedor</th>
                        <th class="text-right p-2 w-40">Móveis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($report['mobile_top'] ?? []) as $i => $row)
                        <tr class="border-b">
                            <td class="p-2">{{ $i + 1 }}</td>
                            <td class="p-2">{{ $row['name'] ?? '-' }}</td>
                            <td class="p-2 text-right tabular-nums">
                                {{ number_format($row['count'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach

                    @if (empty($report['mobile_top']))
                        <tr>
                            <td colspan="3" class="p-3 text-gray-500">Sem dados.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="text-sm text-gray-500 mb-2">IPs mais recentes — {{ $report['mobile_recent_provider'] ?? '-' }}</div>

        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left p-2 w-44">IP</th>
                        <th class="text-left p-2 w-44">Data/Hora</th>
                        <th class="text-left p-2 w-24">TZ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($report['mobile_recent_ips'] ?? []) as $row)
                        <tr class="border-b">
                            <td class="p-2 font-mono">{{ $row['ip'] ?? '-' }}</td>
                            <td class="p-2 tabular-nums">{{ $row['datetime'] ?? '-' }}</td>
                            <td class="p-2">{{ $row['tz'] ?? '-' }}</td>
                        </tr>
                    @endforeach

                    @if (empty($report['mobile_recent_ips']))
                        <tr>
                            <td colspan="3" class="p-3 text-gray-500">Sem dados.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
