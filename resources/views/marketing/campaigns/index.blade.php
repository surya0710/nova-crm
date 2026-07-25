@php
    $statusVariant = [
        'draft' => 'neutral',
        'active' => 'success',
        'paused' => 'warning',
        'completed' => 'primary',
    ];
    $columns = [
        __('Campaign'),
        __('Status'),
        ['label' => __('Budget'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Channels'), 'class' => 'hidden lg:table-cell'],
        ['label' => __('Timeline'), 'class' => 'hidden lg:table-cell'],
        ['label' => __('Created by'), 'class' => 'hidden xl:table-cell'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Campaigns')"
        :subtitle="__('Manage marketing campaigns and budgets')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Marketing'), 'href' => route('marketing.home')],
                ['label' => __('Campaigns'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if (auth()->user()->hasAnyPermission(['marketing.manage', 'integrations.manage']))
                <x-ui.button :href="route('marketing.campaigns.create')" variant="primary" size="sm">{{ __('Create Campaign') }}</x-ui.button>
            @endif
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" action="{{ route('marketing.campaigns.index') }}" class="flex flex-wrap items-end gap-3">
                <div class="min-w-[10rem]">
                    <label for="campaign-status" class="sr-only">{{ __('Status') }}</label>
                    <x-forms.select id="campaign-status" name="status" aria-label="{{ __('Status') }}">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $value)
                            <option value="{{ $value }}" @selected($status === $value)>{{ __(ucfirst($value)) }}</option>
                        @endforeach
                    </x-forms.select>
                </div>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($campaigns->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="campaigns"
                    :action-href="auth()->user()->hasAnyPermission(['marketing.manage', 'integrations.manage']) ? route('marketing.campaigns.create') : null"
                />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns">
                @foreach ($campaigns as $campaign)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('marketing.campaigns.show', $campaign) }}" class="group block min-w-0">
                                <p class="text-sm font-semibold text-ink-heading group-hover:text-primary-700 truncate">{{ $campaign->name }}</p>
                                @if ($campaign->description)
                                    <p class="mt-0.5 text-xs text-ink-muted truncate">{{ Str::limit($campaign->description, 80) }}</p>
                                @endif
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$statusVariant[$campaign->status] ?? 'neutral'">{{ $campaign->statusLabel() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-sm text-ink-muted">
                            @if ($campaign->budget_amount)
                                {{ $campaign->budget_currency ?? 'USD' }} {{ number_format((float) $campaign->budget_amount, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell">
                            @if (! empty($campaign->channels))
                                <div class="flex flex-wrap gap-1">
                                    @foreach (array_slice($campaign->channels, 0, 3) as $channel)
                                        <x-ui.badge variant="primary">{{ __(ucfirst(str_replace('_', ' ', $channel))) }}</x-ui.badge>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-sm text-ink-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-sm text-ink-muted">
                            @if ($campaign->starts_at || $campaign->ends_at)
                                {{ $campaign->starts_at?->format('M j, Y') ?? '—' }}
                                –
                                {{ $campaign->ends_at?->format('M j, Y') ?? '—' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 hidden xl:table-cell text-sm text-ink-muted">{{ $campaign->creator?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
