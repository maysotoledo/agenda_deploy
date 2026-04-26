@php
    $headers = $headers ?? [];
    $rows = $rows ?? [];
    $title = $title ?? null;
    $truncated = $truncated ?? null;
@endphp

@if (count($rows) > 0)
    @if ($title)
        <h3>{{ $title }}</h3>
    @endif
    @if (is_array($truncated))
        <p class="muted small">
            Exibindo {{ number_format((int) ($truncated['shown'] ?? count($rows)), 0, ',', '.') }}
            de {{ number_format((int) ($truncated['total'] ?? count($rows)), 0, ',', '.') }} registros no PDF.
        </p>
    @endif
    <table class="table">
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header['label'] ?? $header['key'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($headers as $header)
                        @php
                            $key = $header['key'] ?? null;
                            $value = $key ? data_get($row, $key) : null;
                        @endphp
                        <td>{{ is_array($value) ? implode(', ', $value) : ($value !== null && $value !== '' ? $value : '-') }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
