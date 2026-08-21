<x-layouts.portal>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">{{ $invoice->number }}</h1>
        <a class="text-sm underline" href="{{ route('portal.commercial.invoices.pdf', [$portalOrganization, $invoice]) }}">{{ __('PDF') }}</a>
    </div>
    <p class="mb-2 text-sm">{{ $invoice->status_label }} · {{ $invoice->formatted_total }}</p>
    <p class="mb-4 text-sm">{{ __('Outstanding') }}: {{ number_format($outstanding, 2) }} {{ $invoice->currency }}</p>
    @if ($gatewayConfigured && $outstanding > 0 && $invoice->canAcceptPayment())
        <form method="POST" action="{{ route('portal.commercial.invoices.pay', [$portalOrganization, $invoice]) }}" class="mb-4">
            @csrf
            <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm text-white">{{ __('Pay outstanding balance') }}</button>
        </form>
    @endif
    <table class="w-full text-sm">
        @foreach ($invoice->items as $item)
            <tr class="border-t"><td class="py-2">{{ $item->description }}</td><td class="py-2 text-right">{{ number_format((float) $item->line_total, 2) }}</td></tr>
        @endforeach
    </table>
</x-layouts.portal>
