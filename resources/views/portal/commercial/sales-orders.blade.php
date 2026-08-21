<x-layouts.portal>
    <h1 class="mb-4 text-xl font-semibold">{{ __('Sales orders') }}</h1>
    <ul class="divide-y rounded-xl border border-slate-200 bg-white">
        @forelse ($salesOrders as $order)
            <li class="flex items-center justify-between px-4 py-3">
                <div>
                    <div class="font-medium">{{ $order->number }}</div>
                    <div class="text-xs text-slate-500">{{ $order->status_label }}</div>
                </div>
                <a class="text-sm underline" href="{{ route('portal.commercial.sales-orders.show', [$portalOrganization, $order]) }}">{{ __('Open') }}</a>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No sales orders yet.') }}</li>
        @endforelse
    </ul>
</x-layouts.portal>
