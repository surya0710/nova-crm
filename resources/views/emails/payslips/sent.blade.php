<x-mail::message>
# {{ __('Your Payslip is Ready') }}

{{ __('Hello :name,', ['name' => $payslip->employee?->full_name ?? __('there')]) }}

{{ __('Your payslip :number for :period is attached.', [
    'number' => $payslip->payslip_number,
    'period' => $payslip->payrollRun?->period?->name ?? __('the latest payroll period'),
]) }}

{{ __('Net salary: :amount', ['amount' => number_format((float) $payslip->net_salary, 2)]) }}

{{ __('You can also view your payslips in Employee Self-Service.') }}

{{ __('Thanks,') }}<br>
{{ $organization->name }}
</x-mail::message>
