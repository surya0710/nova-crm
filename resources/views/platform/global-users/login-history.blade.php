@php
    $navItems = [
        ['label' => __('All Users'), 'href' => route('platform.global-users.index')],
        ['label' => __('Login History'), 'href' => route('platform.global-users.login-history'), 'active' => true],
        ['label' => __('Sessions'), 'href' => route('platform.global-users.sessions')],
        ['label' => __('MFA Status'), 'href' => route('platform.global-users.mfa')],
        ['label' => __('Platform Users'), 'href' => route('platform.users.index')],
    ];
@endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Login History')"
        :subtitle="__('Authentication events across tenant users')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Global Users'), 'href' => route('platform.global-users.index')],
                ['label' => __('Login History'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-4">
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Global users navigation') }}">
                @foreach ($navItems as $item)
                    <x-ui.button :href="$item['href']" :variant="($item['active'] ?? false) ? 'primary' : 'ghost'" size="sm">{{ $item['label'] }}</x-ui.button>
                @endforeach
            </nav>
        </x-ui.card>

        <x-slot:filters>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-forms.field :label="__('Search')" name="search" class="min-w-[16rem] flex-1">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" />
                </x-forms.field>
                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($logs->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="platform_audit" :description="__('Login events will appear here.')" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('When') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Event') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('User') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Organization') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Subject') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($logs as $log)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3 text-ink-muted">{{ $log->created_at->format('M j, Y H:i') }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $log->event }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $log->user?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $log->organization?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $log->subject ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $logs->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
