@php
    $columns = [__('Organization'), __('Plan'), __('Users'), __('Status'), __('Actions')];
@endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Active Subscriptions')"
        :subtitle="__('Organizations with active paid or standard subscriptions')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Subscriptions'), 'href' => route('platform.subscriptions.index')],
                ['label' => __('Active'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <x-forms.field :label="__('Search')" name="search" class="sm:col-span-2">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" />
                </x-forms.field>
                <x-forms.field :label="__('Plan')" name="plan">
                    <x-forms.select name="plan">
                        <option value="">{{ __('All plans') }}</option>
                        @foreach ($plans as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['plan'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($organizations->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="subscriptions" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                @foreach ($columns as $column)
                                    <th scope="col" class="px-4 py-3 text-left font-medium">{{ is_array($column) ? $column['label'] : $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($organizations as $organization)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('platform.organizations.show', $organization) }}" class="font-medium text-ink-heading hover:text-primary-700">
                                            {{ $organization->name }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-ink">{{ $organization->planLabel() }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ number_format($organization->users_count) }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge variant="success">{{ $organization->status->label() }}</x-ui.badge>
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
