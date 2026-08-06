@php
    $quoteStatusVariant = [
        'draft' => 'neutral',
        'sent' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
        'expired' => 'warning',
        'converted' => 'primary',
    ];
    $invoiceStatusVariant = [
        'draft' => 'neutral',
        'issued' => 'info',
        'partially_paid' => 'warning',
        'paid' => 'success',
        'cancelled' => 'neutral',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('Revenue')"
        :subtitle="__('Quotations, invoices, and payments at a glance')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Revenue'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                @if ($canQuotes)
                    <x-ui.button :href="route('quotations.index')" variant="secondary" size="sm">{{ crm_term('quotations') }}</x-ui.button>
                @endif
                @if ($canInvoices)
                    <x-ui.button :href="route('invoices.index')" variant="secondary" size="sm">{{ crm_term('invoices') }}</x-ui.button>
                @endif
                @if ($canPayments)
                    <x-ui.button :href="route('payments.index')" variant="secondary" size="sm">{{ crm_term('payments') }}</x-ui.button>
                @endif
                @if (auth()->user()->hasPermission('reports.view'))
                    <x-ui.button :href="route('reports.finance')" variant="primary" size="sm">{{ __('Finance report') }}</x-ui.button>
                @endif
            </div>
        </x-slot:actions>

        <x-slot:kpis>
            @if ($canQuotes)
                <x-ui.stat-card :label="crm_term('quotations')" :value="(string) $quotationCount" />
            @endif
            @if ($canInvoices)
                <x-ui.stat-card :label="crm_term('invoices')" :value="(string) $invoiceCount" />
            @endif
            @if ($canPayments)
                <x-ui.stat-card :label="crm_term('payments')" :value="(string) $paymentCount" />
            @endif
            @if ($canInvoices && $outstanding !== null)
                <x-ui.stat-card
                    :label="__('Outstanding')"
                    :value="number_format($outstanding, 2)"
                    :hint="__('Open invoice balances')"
                />
            @endif
        </x-slot:kpis>

        <div class="space-y-6">
            @if ($canQuotes)
                <x-workspace.widget
                    :title="__('Recent quotations')"
                    :href="route('quotations.index')"
                >
                    @forelse ($recentQuotations as $quotation)
                        <a href="{{ route('quotations.show', $quotation) }}" class="flex items-center justify-between gap-3 py-2.5 border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-heading truncate hover:text-primary-700">{{ $quotation->number }}</p>
                                <p class="text-xs text-ink-muted truncate">{{ $quotation->customer?->display_name ?? '—' }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs text-ink-muted hidden sm:inline">{{ $quotation->formatted_total }}</span>
                                <x-ui.badge :variant="$quoteStatusVariant[$quotation->status] ?? 'neutral'">{{ $quotation->status_label }}</x-ui.badge>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No quotations yet.') }}</p>
                    @endforelse
                </x-workspace.widget>
            @endif

            @if ($canInvoices)
                <x-workspace.widget
                    :title="__('Recent invoices')"
                    :href="route('invoices.index')"
                >
                    @forelse ($recentInvoices as $invoice)
                        <a href="{{ route('invoices.show', $invoice) }}" class="flex items-center justify-between gap-3 py-2.5 border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-heading truncate hover:text-primary-700">{{ $invoice->number }}</p>
                                <p class="text-xs text-ink-muted truncate">{{ $invoice->customer?->display_name ?? '—' }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs text-ink-muted hidden sm:inline">{{ $invoice->formatted_balance_due }}</span>
                                <x-ui.badge :variant="$invoiceStatusVariant[$invoice->status] ?? 'neutral'">{{ $invoice->status_label }}</x-ui.badge>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No invoices yet.') }}</p>
                    @endforelse
                </x-workspace.widget>
            @endif

            @if ($canPayments)
                <x-workspace.widget
                    :title="__('Recent payments')"
                    :href="route('payments.index')"
                >
                    @forelse ($recentPayments as $payment)
                        <a href="{{ route('payments.show', $payment) }}" class="flex items-center justify-between gap-3 py-2.5 border-b border-line last:border-0">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink-heading truncate hover:text-primary-700">{{ $payment->number }}</p>
                                <p class="text-xs text-ink-muted truncate">{{ $payment->customer?->display_name ?? '—' }}</p>
                            </div>
                            <span class="text-sm font-medium text-ink-heading shrink-0">{{ $payment->formatted_amount }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No payments recorded yet.') }}</p>
                    @endforelse
                </x-workspace.widget>
            @endif
        </div>
    </x-layouts.workspace-home>
</x-app-layout>
