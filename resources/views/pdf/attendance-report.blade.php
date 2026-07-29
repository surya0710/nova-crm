<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['report_label'] ?? 'Attendance Report' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        p { margin: 0 0 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; font-size: 10px; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>{{ $report['report_label'] ?? '' }}</h1>
    <p>{{ $report['filters']['month_label'] ?? '' }} · {{ $report['generated_at'] ?? '' }}</p>

    <table>
        @foreach ($rows as $index => $row)
            @if ($index < 4)
                @continue
            @endif
            <tr>
                @foreach ($row as $cell)
                    @if ($index === 4)
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
