@php
    $subscriptionLinks = [
        ['label' => __('Active'), 'href' => route('platform.subscriptions.active'), 'count' => $overview['active'] ?? 0],
        ['label' => __('Trials'), 'href' => route('platform.subscriptions.trials'), 'count' => $overview['trials'] ?? 0],
        ['label' => __('Renewals'), 'href' => route('platform.subscriptions.renewals'), 'count' => $overview['renewals_due'] ?? 0],
        ['label' => __('Plans'), 'href' => route('platform.plans.index')],
        ['label' => __('Coupons'), 'href' => route('platform.coupons.index'), 'count' => $overview['coupons_active'] ?? 0],
        ['label' => __('Invoices'), 'href' => route('platform.invoices.index'), 'count' => $overview['invoices_open'] ?? 0],
        ['label' => __('Transactions'), 'href' => route('platform.transactions.index')],
    ];
@endphp

<x-platform-layout>
    <x-layouts.dashboard
        :title="__('Subscriptions')"
        :subtitle="__('Billing, plans, and subscription lifecycle')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Subscriptions'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:kpis>
            <x-ui.stat-card :label="__('Active Subscriptions')" :value="number_format($overview['active'] ?? 0)" />
            <x-ui.stat-card :label="__('Trials')" :value="number_format($overview['trials'] ?? 0)" />
            <x-ui.stat-card :label="__('Renewals Due')" :value="number_format($overview['renewals_due'] ?? 0)" />
            <x-ui.stat-card :label="__('Open Invoices')" :value="number_format($overview['invoices_open'] ?? 0)" />
        </x-slot:kpis>

        @foreach ($subscriptionLinks as $link)
            <x-ui.card class="flex flex-col justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-ink-heading">{{ $link['label'] }}</h2>
                    @isset($link['count'])
                        <p class="mt-2 text-2xl font-semibold text-ink-heading">{{ number_format($link['count']) }}</p>
                    @else
                        <p class="mt-2 text-sm text-ink-muted">{{ __('Manage catalog and pricing') }}</p>
                    @endisset
                </div>
                <div class="mt-4">
                    <x-ui.button :href="$link['href']" variant="secondary" size="sm">{{ __('Open') }}</x-ui.button>
                </div>
            </x-ui.card>
        @endforeach

        <x-ui.card class="md:col-span-2 xl:col-span-3">
            <x-slot:header>
                <h2 class="text-sm font-semibold text-ink-heading">{{ __('Subscriptions by Plan') }}</h2>
            </x-slot:header>
            @if (empty($overview['by_plan']))
                <x-ui.empty-state-preset variant="subscriptions" />
            @else
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($overview['by_plan'] as $plan => $count)
                        <div class="rounded-lg border border-line bg-surface-muted/30 px-4 py-3">
                            <dt class="text-xs text-ink-muted">{{ config("platform.plans.$plan", $plan) }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-ink-heading">{{ number_format($count) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </x-ui.card>
    </x-layouts.dashboard>
</x-platform-layout>
