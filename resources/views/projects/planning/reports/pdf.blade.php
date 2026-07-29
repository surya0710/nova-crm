<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report['report_label'] ?? __('Planning Report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>{{ $report['report_label'] ?? __('Planning Report') }}</h1>
    <div class="meta">
        {{ $report['filters']['from'] ?? '' }} → {{ $report['filters']['to'] ?? '' }}
        · {{ $report['generated_at'] ?? '' }}
    </div>
    <table>
        @foreach ($rows as $index => $row)
            <tr>
                @foreach ($row as $cell)
                    @if ($index === 5)
                        <th>{{ $cell }}</th>
                    @else
                        <td>{{ $cell }}</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
    </table>
</body>
</html>
