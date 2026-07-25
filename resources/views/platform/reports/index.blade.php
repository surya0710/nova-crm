<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Platform Reports')"
        :subtitle="__('Cross-tenant analytics and growth metrics')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('platform.reports.export', $filters)" variant="secondary" size="sm">{{ __('Export CSV') }}</x-ui.button>
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-forms.field :label="__('From')" name="from">
                    <x-forms.input type="date" name="from" value="{{ $filters['from'] ?? '' }}" />
                </x-forms.field>
                <x-forms.field :label="__('To')" name="to">
                    <x-forms.input type="date" name="to" value="{{ $filters['to'] ?? '' }}" />
                </x-forms.field>
                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Apply') }}</x-ui.button>
            </form>
        </x-slot:filters>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <x-ui.stat-card :label="__('Revenue Managed')" :value="number_format($report['revenue_managed']['total'], 2)" />
            <x-ui.stat-card :label="__('Invoices')" :value="number_format($report['invoices']['total'])" />
            <x-ui.stat-card :label="__('Payments')" :value="number_format($report['payments']['total'], 2)" />
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ([
                'organizations_growth' => __('Organizations Growth'),
                'users_growth' => __('Users Growth'),
                'lead_volume' => __('Lead Volume'),
                'customer_growth' => __('Customer Growth'),
            ] as $key => $title)
                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-sm font-semibold text-ink-heading">{{ $title }}</h2>
                    </x-slot:header>
                    <div class="divide-y divide-line text-sm">
                        @forelse ($report[$key] as $row)
                            <div class="flex justify-between py-2">
                                <span class="text-ink-muted">{{ $row->period }}</span>
                                <span class="font-medium text-ink-heading">{{ number_format($row->count) }}</span>
                            </div>
                        @empty
                            <x-ui.empty-state-preset variant="reports" />
                        @endforelse
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <x-ui.card class="mt-6">
            <x-slot:header>
                <h2 class="text-sm font-semibold text-ink-heading">{{ __('Top Active Organizations (30 days)') }}</h2>
            </x-slot:header>
            <div class="divide-y divide-line text-sm">
                @forelse ($report['top_active_organizations'] as $org)
                    <div class="flex justify-between gap-4 py-2">
                        <span class="font-medium text-ink-heading">{{ $org->name }}</span>
                        <span class="text-ink-muted">{{ number_format($org->activity_count ?? 0) }} {{ __('events') }}</span>
                    </div>
                @empty
                    <x-ui.empty-state-preset variant="reports" />
                @endforelse
            </div>
        </x-ui.card>
    </x-layouts.entity-listing>
</x-platform-layout>
