<x-layouts.portal>
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-xl font-semibold">{{ $salesOrder->number }}</h1>
        <a class="text-sm underline" href="{{ route('portal.commercial.sales-orders.pdf', [$portalOrganization, $salesOrder]) }}">{{ __('PDF') }}</a>
    </div>
    <p class="mb-4 text-sm text-slate-500">{{ $salesOrder->status_label }} · {{ $salesOrder->formatted_total }}</p>
    <table class="w-full text-sm">
        @foreach ($salesOrder->items as $item)
            <tr class="border-t"><td class="py-2">{{ $item->description }}</td><td class="py-2 text-right">{{ number_format((float) $item->line_total, 2) }}</td></tr>
        @endforeach
    </table>
</x-layouts.portal>
