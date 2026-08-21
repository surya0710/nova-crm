<x-layouts.portal>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ __('Billing') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Your quotations, orders, invoices, and payments.') }}</p>
            </div>
        </div>
        @if (! ($summary['linked'] ?? false))
            <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm">{{ __('This portal login is not linked to a customer account.') }}</p>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['label' => __('Outstanding'), 'value' => number_format((float) $summary['outstanding'], 2).' '.($summary['currency'] ?? '')],
                    ['label' => __('Invoices'), 'value' => $summary['invoices']],
                    ['label' => __('Quotations'), 'value' => $summary['quotations']],
                    ['label' => __('Payments'), 'value' => $summary['payments']],
                ] as $widget)
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="text-xs uppercase text-slate-500">{{ $widget['label'] }}</div>
                        <div class="mt-2 text-2xl font-semibold">{{ $widget['value'] }}</div>
                    </div>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-3 text-sm">
                <a class="underline" href="{{ route('portal.commercial.quotations', $portalOrganization) }}">{{ __('Quotations') }}</a>
                <a class="underline" href="{{ route('portal.commercial.sales-orders', $portalOrganization) }}">{{ __('Sales orders') }}</a>
                <a class="underline" href="{{ route('portal.commercial.invoices', $portalOrganization) }}">{{ __('Invoices') }}</a>
                <a class="underline" href="{{ route('portal.commercial.payments', $portalOrganization) }}">{{ __('Payments') }}</a>
                <a class="underline" href="{{ route('portal.commercial.notes', $portalOrganization) }}">{{ __('Credit / debit notes') }}</a>
            </div>
            @if (! empty($summary['customer']))
                <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm">
                    <div class="font-medium">{{ $summary['customer']->display_name }}</div>
                    <div class="text-slate-500">{{ $summary['customer']->email }}</div>
                </div>
            @endif
        @endif
    </div>
</x-layouts.portal>
