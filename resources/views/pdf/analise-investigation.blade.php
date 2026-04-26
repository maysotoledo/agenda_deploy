<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Investigação {{ $investigation['name'] }}</title>
    <style>
        @page { margin: 24px 20px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; line-height: 1.4; }
        h1, h2, h3 { margin: 0; }
        h1 { font-size: 20px; margin-bottom: 6px; }
        h2 { font-size: 15px; margin: 18px 0 8px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        h3 { font-size: 12px; margin: 14px 0 6px; }
        p { margin: 0 0 6px; }
        .muted { color: #6b7280; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 8px -8px 0; }
        .card { border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; vertical-align: top; }
        .card .label { color: #6b7280; font-size: 10px; display: block; margin-bottom: 4px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; text-align: left; }
        .table th { background: #f3f4f6; font-size: 10px; }
        .run { page-break-before: always; }
        .run:first-of-type { page-break-before: auto; }
        .pill { display: inline-block; border: 1px solid #d1d5db; border-radius: 999px; padding: 2px 8px; font-size: 10px; margin-right: 6px; }
        .list { margin: 6px 0 0 16px; padding: 0; }
        .list li { margin-bottom: 4px; }
        .small { font-size: 10px; }
        .brand-logo { text-align: center; margin: 0 0 14px; }
        .brand-logo img { max-width: 220px; max-height: 220px; }
    </style>
</head>
<body>
    @foreach ($runs as $run)
        @php
            $report = $run['report'] ?? null;
            $source = $investigation['source'] ?? 'generico';
            $pdfTruncated = is_array($report) ? (array) data_get($report, '_pdf_truncated', []) : [];
        @endphp

        <div class="run">
            @if (! empty($brand_logo_data_uri))
                <div class="brand-logo">
                    <img src="{{ $brand_logo_data_uri }}" alt="SACAT">
                </div>
            @endif

            @if (! is_array($report))
                <h1>{{ $investigation['name'] }}</h1>
                <p class="muted">
                    Plataforma: {{ $investigation['source_label'] }} |
                    Investigação ID: {{ $investigation['id'] }} |
                    Gerado em: {{ $generated_at }}
                </p>
                <p class="muted" style="margin-top: 10px;">Sem dados consolidados para exportar neste run.</p>
                @continue
            @endif

            @if ($source === 'whatsapp')
                <h1>Análise Inteligente</h1>
                <p>Gerado em: {{ data_get($report, 'generated_at', $generated_at) }}</p>
                <p>Arquivo processado Hash SHA-256: {{ data_get($report, 'file_hash', '-') }}</p>
            @else
                <h1>{{ $investigation['name'] }}</h1>
                <p class="muted">
                    Plataforma: {{ $investigation['source_label'] }} |
                    Investigação ID: {{ $investigation['id'] }} |
                    Gerado em: {{ $generated_at }}
                </p>
                <p class="muted">
                    Run #{{ $run['id'] }} |
                    Alvo: {{ $run['target'] }} |
                    Status: {{ $run['status'] }} |
                    Progresso: {{ $run['progress'] }}%
                </p>
            @endif

            <h3>Resumo</h3>
            <table class="grid">
                <tr>
                    <td class="card">
                        <span class="label">Alvo</span>
                        {{ $run['target'] }}
                    </td>
                    <td class="card">
                        <span class="label">Período</span>
                        {{ data_get($report, 'period_label', '-') }}
                    </td>
                    <td class="card">
                        <span class="label">Total de eventos</span>
                        {{ number_format((int) data_get($report, 'total_events', data_get($report, 'total_ips', 0)), 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td class="card">
                        <span class="label">IPs únicos</span>
                        {{ number_format((int) data_get($report, 'total_unique_ips', count(data_get($report, 'unique_ip_rows', []))), 0, ',', '.') }}
                    </td>
                    <td class="card">
                        <span class="label">Eventos noturnos</span>
                        {{ number_format((int) data_get($report, 'night_total_events', count(data_get($report, 'night_events_rows', []))), 0, ',', '.') }}
                    </td>
                    <td class="card">
                        <span class="label">Eventos móveis</span>
                        {{ number_format((int) data_get($report, 'mobile_total_events', count(data_get($report, 'mobile_events_rows', []))), 0, ',', '.') }}
                    </td>
                </tr>
            </table>

            @if (is_array(data_get($report, 'subscriber_info')))
                <h3>Subscriber Info</h3>
                <table class="table">
                    <tbody>
                        @foreach (data_get($report, 'subscriber_info', []) as $key => $value)
                            <tr>
                                <th style="width: 180px;">{{ str_replace('_', ' ', (string) $key) }}</th>
                                <td>{{ is_array($value) ? implode(', ', $value) : ($value !== null && $value !== '' ? $value : '-') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($source === 'instagram')
                <h3>Conta Instagram</h3>
                <table class="table">
                    <tbody>
                        <tr><th style="width: 180px;">Conta</th><td>{{ data_get($report, 'vanity_name', '-') }}</td></tr>
                        <tr><th>Identificador</th><td>{{ data_get($report, 'account_identifier', '-') }}</td></tr>
                        <tr><th>Nome</th><td>{{ data_get($report, 'first_name', '-') }}</td></tr>
                        <tr><th>Data de registro</th><td>{{ data_get($report, 'registration_date', '-') }}</td></tr>
                        <tr><th>Telefone de registro</th><td>{{ data_get($report, 'registration_phone_formatted', '-') }}</td></tr>
                        <tr><th>Última localização</th><td>{{ data_get($report, 'last_location_latitude', '-') }}, {{ data_get($report, 'last_location_longitude', '-') }}</td></tr>
                    </tbody>
                </table>
            @endif

            @if ($source === 'whatsapp')
                <h3>WhatsApp</h3>
                <table class="table">
                    <tbody>
                        <tr><th style="width: 180px;">Gerado em</th><td>{{ data_get($report, 'generated_at', '-') }}</td></tr>
                        <tr><th>Hash SHA-256</th><td>{{ data_get($report, 'file_hash', '-') }}</td></tr>
                        <tr><th style="width: 180px;">Dispositivo</th><td>{{ data_get($report, 'device', '-') }}</td></tr>
                        <tr><th>Emails de registro</th><td>{{ implode(', ', data_get($report, 'registered_emails', [])) ?: '-' }}</td></tr>
                        <tr><th>Contatos simétricos</th><td>{{ number_format((int) data_get($report, 'symmetric_contacts_count', 0), 0, ',', '.') }}</td></tr>
                        <tr><th>Contatos assimétricos</th><td>{{ number_format((int) data_get($report, 'asymmetric_contacts_count', 0), 0, ',', '.') }}</td></tr>
                        <tr><th>Último IP de conexão</th><td>{{ data_get($report, 'connection_summary.last_ip', '-') }}</td></tr>
                        <tr><th>Última operadora</th><td>{{ data_get($report, 'connection_summary.last_ip_provider', '-') }}</td></tr>
                        <tr><th>Última vez visto</th><td>{{ data_get($report, 'connection_summary.last_seen', '-') }}</td></tr>
                    </tbody>
                </table>

                @if (count(data_get($report, 'provider_ranking_top', [])) > 0)
                    <h3>Ranking Geral de Provedores</h3>
                    <p class="muted small">Provedores com maior número de ocorrências de IPs.</p>
                    <ol class="list">
                        @foreach (data_get($report, 'provider_ranking_top', []) as $row)
                            <li>{{ $row['provider'] }} ({{ number_format((int) ($row['occurrences'] ?? 0), 0, ',', '.') }} ocorrências)</li>
                        @endforeach
                    </ol>
                @endif

                @if (count(data_get($report, 'fixed_night_top', [])) > 0)
                    <h3>Análise de Internet Residencial</h3>
                    <p class="muted small">Provedores com mais IPs durante período noturno (23h-06h) em conexões fixas.</p>
                    <ol class="list">
                        @foreach (data_get($report, 'fixed_night_top', []) as $row)
                            <li>{{ $row['provider'] }} ({{ number_format((int) ($row['occurrences'] ?? 0), 0, ',', '.') }} noturnos)</li>
                        @endforeach
                    </ol>
                @endif

                @if (count(data_get($report, 'fixed_recent_ips', [])) > 0)
                    @include('pdf.partials.simple-table', [
                        'title' => 'IPs Mais Recentes - Internet Residencial',
                        'headers' => [
                            ['key' => 'ip', 'label' => 'Endereço de IP'],
                            ['key' => 'last_seen', 'label' => 'Data/Hora'],
                            ['key' => 'connection_type', 'label' => 'Tipo'],
                        ],
                        'rows' => data_get($report, 'fixed_recent_ips', []),
                    ])
                @endif

                @if (count(data_get($report, 'mobile_top', [])) > 0)
                    <h3>Análise de Operadora Móvel</h3>
                    <p class="muted small">Provedores com mais IPs em conexões móveis.</p>
                    <ol class="list">
                        @foreach (data_get($report, 'mobile_top', []) as $row)
                            <li>{{ $row['provider'] }} ({{ number_format((int) ($row['occurrences'] ?? 0), 0, ',', '.') }} móveis)</li>
                        @endforeach
                    </ol>
                @endif

                @if (count(data_get($report, 'mobile_recent_ips', [])) > 0)
                    @include('pdf.partials.simple-table', [
                        'title' => 'IPs Mais Recentes - Operadora Móvel',
                        'headers' => [
                            ['key' => 'ip', 'label' => 'Endereço de IP'],
                            ['key' => 'last_seen', 'label' => 'Data/Hora'],
                            ['key' => 'connection_type', 'label' => 'Tipo'],
                        ],
                        'rows' => data_get($report, 'mobile_recent_ips', []),
                    ])
                @endif

                @if (count(data_get($report, 'city_ranking_top', [])) > 0)
                    <h3>Análise de Cidades</h3>
                    <p class="muted small">Cidades com maior número de ocorrências de IPs.</p>
                    <ol class="list">
                        @foreach (data_get($report, 'city_ranking_top', []) as $row)
                            <li>{{ $row['city'] }} ({{ number_format((int) ($row['occurrences'] ?? 0), 0, ',', '.') }} ocorrências)</li>
                        @endforeach
                    </ol>
                @endif
            @endif

            @if (! empty(data_get($report, 'investigation_hints', [])))
                <h3>Indícios</h3>
                <ul class="list">
                    @foreach (data_get($report, 'investigation_hints', []) as $hint)
                        <li>{{ $hint }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($source !== 'whatsapp')
                @include('pdf.partials.simple-table', [
                    'title' => 'Timeline',
                    'truncated' => $pdfTruncated['timeline_rows'] ?? null,
                    'headers' => [
                        ['key' => 'datetime', 'label' => 'Data/Hora'],
                        ['key' => 'ip', 'label' => 'IP'],
                        ['key' => 'provider', 'label' => 'Provedor'],
                        ['key' => 'city', 'label' => 'Cidade'],
                        ['key' => 'type', 'label' => 'Tipo'],
                        ['key' => 'action', 'label' => 'Ação'],
                    ],
                    'rows' => data_get($report, 'timeline_rows', []),
                ])

                @include('pdf.partials.simple-table', [
                    'title' => 'IPs Únicos',
                    'truncated' => $pdfTruncated['unique_ip_rows'] ?? null,
                    'headers' => [
                        ['key' => 'ip', 'label' => 'IP'],
                        ['key' => 'provider', 'label' => 'Provedor'],
                        ['key' => 'city', 'label' => 'Cidade'],
                        ['key' => 'type', 'label' => 'Tipo'],
                        ['key' => 'count', 'label' => 'Ocorrências'],
                        ['key' => 'last_seen', 'label' => 'Último'],
                    ],
                    'rows' => data_get($report, 'unique_ip_rows', []),
                ])

                @include('pdf.partials.simple-table', [
                    'title' => 'Provedores',
                    'truncated' => $pdfTruncated['provider_stats_rows'] ?? null,
                    'headers' => [
                        ['key' => 'provider', 'label' => 'Provedor'],
                        ['key' => 'occurrences', 'label' => 'Ocorrências'],
                        ['key' => 'unique_ips', 'label' => 'IPs únicos'],
                        ['key' => 'cities', 'label' => 'Cidades'],
                        ['key' => 'mobile_percent', 'label' => '% móvel'],
                        ['key' => 'last_seen', 'label' => 'Último'],
                    ],
                    'rows' => data_get($report, 'provider_stats_rows', []),
                ])

                @include('pdf.partials.simple-table', [
                    'title' => 'Cidades',
                    'truncated' => $pdfTruncated['city_stats_rows'] ?? null,
                    'headers' => [
                        ['key' => 'city', 'label' => 'Cidade'],
                        ['key' => 'occurrences', 'label' => 'Ocorrências'],
                        ['key' => 'unique_ips', 'label' => 'IPs únicos'],
                        ['key' => 'providers', 'label' => 'Provedores'],
                        ['key' => 'mobile_percent', 'label' => '% móvel'],
                        ['key' => 'last_seen', 'label' => 'Último'],
                    ],
                    'rows' => data_get($report, 'city_stats_rows', []),
                ])
            @endif

            @if ($source === 'instagram' && (count(data_get($report, 'followers', [])) > 0 || count(data_get($report, 'following', [])) > 0))
                <h3>Relacionamentos</h3>
                <table class="table">
                    <tbody>
                        <tr>
                            <th style="width: 180px;">Followers</th>
                            <td>{{ implode(', ', data_get($report, 'followers', [])) ?: '-' }}</td>
                        </tr>
                        <tr>
                            <th>Following</th>
                            <td>{{ implode(', ', data_get($report, 'following', [])) ?: '-' }}</td>
                        </tr>
                    </tbody>
                </table>
                @if (isset($pdfTruncated['followers']) || isset($pdfTruncated['following']))
                    <p class="muted small">
                        Listas de relacionamentos foram reduzidas no PDF para evitar timeout.
                    </p>
                @endif
            @endif

            @if ($source === 'instagram' && count(data_get($report, 'direct_threads', [])) > 0)
                <h3>Direct</h3>
                @if (isset($pdfTruncated['direct_threads']))
                    <p class="muted small">
                        Exibindo {{ number_format((int) ($pdfTruncated['direct_threads']['shown'] ?? count(data_get($report, 'direct_threads', []))), 0, ',', '.') }}
                        de {{ number_format((int) ($pdfTruncated['direct_threads']['total'] ?? count(data_get($report, 'direct_threads', []))), 0, ',', '.') }} conversas no PDF.
                    </p>
                @endif
                <table class="table">
                    <thead>
                        <tr>
                            <th>Participantes</th>
                            <th>Mensagens</th>
                            <th>Última</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (data_get($report, 'direct_threads', []) as $thread)
                            <tr>
                                <td>{{ implode(', ', data_get($thread, 'participants', [])) ?: '-' }}</td>
                                <td>{{ number_format((int) data_get($thread, 'messages_count', count(data_get($thread, 'messages', []))), 0, ',', '.') }}</td>
                                <td>{{ data_get($thread, 'last_message_at', '-') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($source === 'whatsapp')
                @include('pdf.partials.simple-table', [
                    'title' => 'Grupos',
                    'truncated' => $pdfTruncated['groups_rows'] ?? null,
                    'headers' => [
                        ['key' => 'group', 'label' => 'Grupo'],
                        ['key' => 'participants_count', 'label' => 'Participantes'],
                        ['key' => 'admins_count', 'label' => 'Admins'],
                    ],
                    'rows' => data_get($report, 'groups_rows', []),
                ])

                @include('pdf.partials.simple-table', [
                    'title' => 'Bilhetagem',
                    'truncated' => $pdfTruncated['bilhetagem_cards'] ?? null,
                    'headers' => [
                        ['key' => 'recipient', 'label' => 'Contato'],
                        ['key' => 'total', 'label' => 'Mensagens'],
                        ['key' => 'latest.timestamp', 'label' => 'Última'],
                        ['key' => 'latest.sender_ip', 'label' => 'IP'],
                        ['key' => 'latest_provider', 'label' => 'Provedor'],
                        ['key' => 'latest_city', 'label' => 'Cidade'],
                    ],
                    'rows' => data_get($report, 'bilhetagem_cards', []),
                ])
            @endif

            @if (($source === 'google' || $source === 'whatsapp') && count(data_get($report, 'vinculo_rows', [])) > 0)
                @include('pdf.partials.simple-table', [
                    'title' => 'Vínculo',
                    'truncated' => $pdfTruncated['vinculo_rows'] ?? null,
                    'headers' => [
                        ['key' => 'ip', 'label' => 'IP'],
                        ['key' => 'targets', 'label' => 'Alvos'],
                        ['key' => 'targets_count', 'label' => 'Qtd. alvos'],
                        ['key' => 'total_occurrences', 'label' => 'Ocorrências'],
                        ['key' => 'provider', 'label' => 'Provedor'],
                        ['key' => 'city', 'label' => 'Cidade'],
                        ['key' => 'last_seen', 'label' => 'Último'],
                    ],
                    'rows' => data_get($report, 'vinculo_rows', []),
                ])
            @endif

            @if (count(data_get($report, 'maps_rows', [])) > 0)
                @include('pdf.partials.simple-table', [
                    'title' => 'Maps',
                    'truncated' => $pdfTruncated['maps_rows'] ?? null,
                    'headers' => [
                        ['key' => 'datetime', 'label' => 'Data/Hora'],
                        ['key' => 'type', 'label' => 'Tipo'],
                        ['key' => 'description', 'label' => 'Descrição'],
                        ['key' => 'target', 'label' => 'Destino/Pesquisa'],
                        ['key' => 'origin', 'label' => 'Origem'],
                    ],
                    'rows' => data_get($report, 'maps_rows', []),
                ])
            @endif

            @if (count(data_get($report, 'search_rows', [])) > 0)
                @include('pdf.partials.simple-table', [
                    'title' => 'Pesquisas',
                    'truncated' => $pdfTruncated['search_rows'] ?? null,
                    'headers' => [
                        ['key' => 'datetime', 'label' => 'Data/Hora'],
                        ['key' => 'target', 'label' => 'Pesquisa'],
                    ],
                    'rows' => data_get($report, 'search_rows', []),
                ])
            @endif

            @if (count(data_get($report, 'user_agent_rows', [])) > 0)
                @include('pdf.partials.simple-table', [
                    'title' => 'User Agents',
                    'truncated' => $pdfTruncated['user_agent_rows'] ?? null,
                    'headers' => [
                        ['key' => 'user_agent', 'label' => 'User-Agent'],
                        ['key' => 'occurrences', 'label' => 'Ocorrências'],
                        ['key' => 'last_seen', 'label' => 'Último'],
                    ],
                    'rows' => data_get($report, 'user_agent_rows', []),
                ])
            @endif

            @if (count(data_get($report, 'device_identifier_rows', [])) > 0)
                @include('pdf.partials.simple-table', [
                    'title' => 'Identificadores',
                    'truncated' => $pdfTruncated['device_identifier_rows'] ?? null,
                    'headers' => [
                        ['key' => 'type', 'label' => 'Tipo'],
                        ['key' => 'value', 'label' => 'Identificador'],
                        ['key' => 'occurrences', 'label' => 'Ocorrências'],
                        ['key' => 'last_seen', 'label' => 'Último'],
                    ],
                    'rows' => data_get($report, 'device_identifier_rows', []),
                ])
            @endif
        </div>
    @endforeach
</body>
</html>
