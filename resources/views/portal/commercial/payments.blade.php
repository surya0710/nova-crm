<x-layouts.portal>
    <h1 class="mb-4 text-xl font-semibold">{{ __('Payment history') }}</h1>
    <ul class="divide-y rounded-xl border border-slate-200 bg-white">
        @forelse ($payments as $payment)
            <li class="px-4 py-3">
                <div class="font-medium">{{ $payment->number }} · {{ $payment->formatted_amount }}</div>
                <div class="text-xs text-slate-500">{{ $payment->payment_date?->format('M j, Y') }} · {{ $payment->method_label }}</div>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No payments yet.') }}</li>
        @endforelse
    </ul>
</x-layouts.portal>
