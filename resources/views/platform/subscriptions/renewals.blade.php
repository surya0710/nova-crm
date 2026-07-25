<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Upcoming Renewals')"
        :subtitle="__('Organizations with renewals due in the next 30 days')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Subscriptions'), 'href' => route('platform.subscriptions.index')],
                ['label' => __('Renewals'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-forms.field :label="__('Search')" name="search" class="min-w-[16rem] flex-1">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" />
                </x-forms.field>
                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($organizations->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="subscriptions" :description="__('No renewals due in the next 30 days.')" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Organization') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Plan') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Users') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Renews At') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($organizations as $organization)
                                @php
                                    $renewsAt = data_get($organization->settings, 'subscription.renews_at');
                                @endphp
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('platform.organizations.show', $organization) }}" class="font-medium text-ink-heading hover:text-primary-700">
                                            {{ $organization->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-ink">{{ $organization->planLabel() }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ number_format($organization->users_count) }}</td>
                                    <td class="px-4 py-3 text-ink-muted">
                                        {{ $renewsAt ? \Illuminate\Support\Carbon::parse($renewsAt)->format('M j, Y') : '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-ui.button :href="route('platform.organizations.show', $organization)" variant="ghost" size="sm">{{ __('View') }}</x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $organizations->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
