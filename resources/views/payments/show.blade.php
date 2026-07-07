<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ $payment->number }}</h1>
                <p class="text-sm text-slate-500">{{ $payment->formatted_amount }} · {{ $payment->payment_date->format('M j, Y') }}</p>
            </div>
            @can('delete', $payment)
                <form method="POST" action="{{ route('payments.destroy', $payment) }}" onsubmit="return confirm('{{ __('Delete this payment? The invoice balance will be recalculated.') }}')">
                    @csrf @method('DELETE')
                    <x-danger-button type="submit">{{ __('Delete') }}</x-danger-button>
                </form>
            @endcan
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="max-w-2xl space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <dl class="p-6 space-y-4 text-sm">
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ __('Amount') }}</dt>
                <dd class="font-semibold text-slate-900">{{ $payment->formatted_amount }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ __('Method') }}</dt>
                <dd>{{ $payment->method_label }}</dd>
            </div>
            @if ($payment->reference)
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Reference') }}</dt>
                    <dd>{{ $payment->reference }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ crm_term('invoice') }}</dt>
                <dd><a href="{{ route('invoices.show', $payment->invoice) }}" class="text-indigo-600">{{ $payment->invoice->number }}</a></dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ crm_term('customer') }}</dt>
                <dd>{{ $payment->customer->display_name }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-slate-500">{{ __('Recorded By') }}</dt>
                <dd>{{ $payment->recorder?->name ?? '—' }}</dd>
            </div>
            @if ($payment->notes)
                <div>
                    <dt class="text-slate-500 mb-1">{{ __('Notes') }}</dt>
                    <dd class="text-slate-700 whitespace-pre-line">{{ $payment->notes }}</dd>
                </div>
            @endif
        </dl>
        </div>

        @can('create', App\Models\Payment::class)
            <x-client-email-form
                :action="route('payments.send', $payment)"
                :email="old('email', $payment->customer->email)"
                :submit-label="__('Send Receipt')"
                :title="__('Email Receipt')"
                :description="__('Send payment confirmation to your customer')"
                :organization="$organization"
                :missing-email-hint="! $payment->customer->email"
            />
        @endcan
    </div>

    <a href="{{ route('payments.index') }}" class="inline-block mt-6 text-sm font-medium text-indigo-600">← {{ __('Back to :label', ['label' => strtolower(crm_term('payments'))]) }}</a>
</x-app-layout>
