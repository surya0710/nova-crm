<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $payslip->payslip_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 18px 0 8px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .totals td { font-weight: bold; }
        .section { margin-top: 8px; }
    </style>
</head>
<body>
    <h1>{{ $organization?->name ?? __('Organization') }}</h1>
    <div class="meta">
        <div>{{ __('Payslip') }}: {{ $payslip->payslip_number }}</div>
        <div>{{ __('Period') }}: {{ $period?->name }} ({{ $period?->start_date?->toDateString() }} – {{ $period?->end_date?->toDateString() }})</div>
        <div>{{ __('Generated') }}: {{ $payslip->generated_at?->toDateTimeString() }}</div>
    </div>

    <h2>{{ __('Employee') }}</h2>
    <table>
        <tr><th>{{ __('Name') }}</th><td>{{ $employee?->full_name }}</td></tr>
        <tr><th>{{ __('Code') }}</th><td>{{ $employee?->employee_code }}</td></tr>
        <tr><th>{{ __('Email') }}</th><td>{{ $employee?->email }}</td></tr>
    </table>

    <h2>{{ __('Earnings') }}</h2>
    <table>
        <thead><tr><th>{{ __('Component') }}</th><th class="right">{{ __('Amount') }}</th></tr></thead>
        <tbody>
        @forelse ($earnings as $line)
            <tr>
                <td>{{ $line['name'] ?? $line['code'] ?? '—' }}</td>
                <td class="right">{{ number_format((float) ($line['amount'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="2">{{ __('No earnings') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>{{ __('Deductions') }}</h2>
    <table>
        <thead><tr><th>{{ __('Component') }}</th><th class="right">{{ __('Amount') }}</th></tr></thead>
        <tbody>
        @forelse ($deductions as $line)
            <tr>
                <td>{{ $line['name'] ?? $line['code'] ?? '—' }}</td>
                <td class="right">{{ number_format((float) ($line['amount'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="2">{{ __('No deductions') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>{{ __('Employer Contributions') }}</h2>
    <table>
        <thead><tr><th>{{ __('Component') }}</th><th class="right">{{ __('Amount') }}</th></tr></thead>
        <tbody>
        @forelse ($employerContributions as $line)
            <tr>
                <td>{{ $line['name'] ?? $line['code'] ?? '—' }}</td>
                <td class="right">{{ number_format((float) ($line['amount'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="2">{{ __('No employer contributions') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    @if (!empty($statutory))
    <h2>{{ __('Statutory Summary') }}</h2>
    <table>
        <tr><th>{{ __('PF Employee') }}</th><td class="right">{{ number_format((float) data_get($statutory, 'pf.employee_amount', 0), 2) }}</td></tr>
        <tr><th>{{ __('PF Employer') }}</th><td class="right">{{ number_format((float) data_get($statutory, 'pf.employer_amount', 0), 2) }}</td></tr>
        <tr><th>{{ __('ESI Employee') }}</th><td class="right">{{ number_format((float) data_get($statutory, 'esi.employee_amount', 0), 2) }}</td></tr>
        <tr><th>{{ __('ESI Employer') }}</th><td class="right">{{ number_format((float) data_get($statutory, 'esi.employer_amount', 0), 2) }}</td></tr>
        <tr><th>{{ __('Professional Tax') }}</th><td class="right">{{ number_format((float) data_get($statutory, 'professional_tax.amount', 0), 2) }}</td></tr>
    </table>
    @endif

    <h2>{{ __('Totals') }}</h2>
    <table class="totals">
        <tr><td>{{ __('Gross Salary') }}</td><td class="right">{{ number_format((float) $payslip->gross_salary, 2) }}</td></tr>
        <tr><td>{{ __('Total Deductions') }}</td><td class="right">{{ number_format((float) $payslip->total_deductions, 2) }}</td></tr>
        <tr><td>{{ __('Employer Contributions') }}</td><td class="right">{{ number_format((float) $payslip->employer_contributions, 2) }}</td></tr>
        <tr><td>{{ __('Net Salary') }}</td><td class="right">{{ number_format((float) $payslip->net_salary, 2) }}</td></tr>
    </table>
</body>
</html>
