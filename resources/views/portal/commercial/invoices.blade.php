<x-layouts.portal>
    <h1 class="mb-4 text-xl font-semibold">{{ __('Invoices') }}</h1>
    <ul class="divide-y rounded-xl border border-slate-200 bg-white">
        @forelse ($invoices as $invoice)
            <li class="flex items-center justify-between px-4 py-3">
                <div>
                    <div class="font-medium">{{ $invoice->number }}</div>
                    <div class="text-xs text-slate-500">{{ $invoice->status_label }} · {{ __('Outstanding') }}: {{ number_format($invoice->effective_balance, 2) }} {{ $invoice->currency }}</div>
                </div>
                <a class="text-sm underline" href="{{ route('portal.commercial.invoices.show', [$portalOrganization, $invoice]) }}">{{ __('Open') }}</a>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No invoices yet.') }}</li>
        @endforelse
    </ul>
</x-layouts.portal>
