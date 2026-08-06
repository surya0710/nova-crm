@php
    $canManage = auth('platform')->user()->hasPermission('platform.organizations.manage');
@endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Organizations')"
        :subtitle="__('Manage tenant organizations across the platform')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Organizations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if ($canManage)
                <x-ui.button :href="route('platform.organizations.create')" variant="primary" size="sm">
                    {{ __('New Organization') }}
                </x-ui.button>
            @endif
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <x-forms.field :label="__('Search')" name="search" class="sm:col-span-2">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Name, email…') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="status">
                    <x-forms.select name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Plan')" name="plan">
                    <x-forms.select name="plan">
                        <option value="">{{ __('All plans') }}</option>
                        @foreach ($plans as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['plan'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Created from')" name="created_from">
                    <x-forms.input type="date" name="created_from" value="{{ $filters['created_from'] ?? '' }}" />
                </x-forms.field>
                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-1">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
                    <x-ui.button :href="route('platform.organizations.index')" variant="ghost" size="sm">{{ __('Reset') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($organizations->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="organizations"
                    :action-href="$canManage ? route('platform.organizations.create') : null"
                />
            </x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Company') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium md:table-cell">{{ __('Owner') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Plan') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium lg:table-cell">{{ __('Users') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium lg:table-cell">{{ __('Storage') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium xl:table-cell">{{ __('Created') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium xl:table-cell">{{ __('Last Activity') }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($organizations as $organization)
                                @php $owner = $organization->owners->first(); @endphp
                                <tr class="hover:bg-surface-muted/60 transition">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('platform.organizations.show', $organization) }}" class="font-medium text-ink-heading hover:text-primary-700">
                                            {{ $organization->name }}
                                        </a>
                                        @if ($organization->email)
                                            <p class="text-xs text-ink-muted">{{ $organization->email }}</p>
                                        @endif
                                    </td>
                                    <td class="hidden px-4 py-3 text-ink-muted md:table-cell">{{ $owner?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $organization->planLabel() }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :variant="$organization->isActive() ? 'success' : ($organization->isSuspended() ? 'warning' : 'neutral')">
                                            {{ $organization->status->label() }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="hidden px-4 py-3 text-ink-muted lg:table-cell">{{ number_format($organization->users_count) }}</td>
                                    <td class="hidden px-4 py-3 text-ink-muted lg:table-cell">{{ number_format($organization->storage_used_bytes / 1048576, 1) }} MB</td>
                                    <td class="hidden px-4 py-3 text-ink-muted xl:table-cell">{{ $organization->created_at->format('M j, Y') }}</td>
                                    <td class="hidden px-4 py-3 text-ink-muted xl:table-cell">{{ $organization->last_activity_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <x-ui.button :href="route('platform.organizations.show', $organization)" variant="ghost" size="sm">
                                            {{ __('View') }}
                                        </x-ui.button>
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
