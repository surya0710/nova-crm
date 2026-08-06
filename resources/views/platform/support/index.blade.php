@php $canManage = auth('platform')->user()->hasPermission('platform.support.manage'); @endphp

<x-platform-layout>
    <x-layouts.dashboard
        :title="__('Support')"
        :subtitle="__('Customer support tickets and platform announcements')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Support'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if ($canManage)
                <x-ui.button :href="route('platform.support.tickets.create')" variant="primary" size="sm">{{ __('New Ticket') }}</x-ui.button>
            @endif
        </x-slot:actions>

        <x-slot:kpis>
            <x-ui.stat-card :label="__('Open Tickets')" :value="number_format($overview['open_tickets'] ?? 0)" />
            <x-ui.stat-card :label="__('Resolved Tickets')" :value="number_format($overview['resolved_tickets'] ?? 0)" />
            <x-ui.stat-card :label="__('Maintenance Notices')" :value="number_format($overview['maintenance'] ?? 0)" />
            <x-ui.stat-card :label="__('Broadcasts')" :value="number_format($overview['broadcasts'] ?? 0)" />
        </x-slot:kpis>

        <x-ui.card class="flex flex-col justify-between">
            <div>
                <h2 class="text-sm font-semibold text-ink-heading">{{ __('Support Tickets') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ __('Manage customer issues and requests') }}</p>
            </div>
            <div class="mt-4">
                <x-ui.button :href="route('platform.support.tickets')" variant="secondary" size="sm">{{ __('View Tickets') }}</x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card class="flex flex-col justify-between">
            <div>
                <h2 class="text-sm font-semibold text-ink-heading">{{ __('Announcements') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ __('Maintenance, incidents, and broadcasts') }}</p>
            </div>
            <div class="mt-4">
                <x-ui.button :href="route('platform.support.announcements')" variant="secondary" size="sm">{{ __('View Announcements') }}</x-ui.button>
            </div>
        </x-ui.card>
    </x-layouts.dashboard>
</x-platform-layout>
