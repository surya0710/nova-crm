@php
    $columns = [
        __('Source'),
        __('Medium'),
        __('Channel'),
        ['label' => __('Touches'), 'align' => 'right'],
    ];
    $attributionColumns = [
        __('Lead / Customer'),
        __('Model'),
        ['label' => __('Primary'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Attributed'), 'class' => 'hidden lg:table-cell'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Attribution')"
        :subtitle="__('Marketing touchpoints linked to CRM records')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Marketing'), 'href' => route('marketing.home')],
                ['label' => __('Attribution'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-6 flex flex-wrap items-center gap-3">
            <span class="text-sm text-ink-muted">{{ __('Attribution model') }}</span>
            <x-ui.badge variant="primary">{{ __(ucfirst(str_replace('_', ' ', $model))) }}</x-ui.badge>
        </div>

        <x-workspace.widget :title="__('Conversions summary')" class="mb-6">
            @if ($conversions->isEmpty())
                <x-ui.empty-state-preset variant="attribution" class="!py-4" />
            @else
                <ul class="divide-y divide-line -mx-1">
                    @foreach ($conversions as $row)
                        <li class="py-2.5 flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-ink-heading">{{ $row->event_name ?? __('Conversion') }}</span>
                            <span class="text-ink-muted">
                                {{ number_format((int) $row->total) }}
                                @if ($row->value)
                                    · {{ number_format((float) $row->value, 2) }}
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('By source')" class="mb-6">
            @if ($bySource->isEmpty())
                <x-ui.empty-state-preset variant="attribution" class="!py-4" />
            @else
                <x-tables.table :columns="$columns">
                    @foreach ($bySource as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm text-ink-heading">{{ $row->source ?: __('(direct)') }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $row->medium ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ $row->channel ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-ink-heading">{{ number_format((int) $row->total) }}</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        </x-workspace.widget>

        <x-workspace.widget :title="__('Attributions')">
            @if ($attributions->isEmpty())
                <x-ui.empty-state-preset variant="attribution" />
            @else
                <x-tables.table :columns="$attributionColumns">
                    @foreach ($attributions as $attribution)
                        <tr class="hover:bg-surface-muted/60 transition">
                            <td class="px-4 py-3 text-sm">
                                @if ($attribution->lead)
                                    <a href="{{ route('leads.show', $attribution->lead) }}" class="font-medium text-primary-600 hover:text-primary-700">{{ $attribution->lead->name }}</a>
                                @elseif ($attribution->customer)
                                    <span class="font-medium text-ink-heading">{{ $attribution->customer->display_name ?? __('Customer') }}</span>
                                @else
                                    <span class="text-ink-muted">{{ __('Anonymous visitor') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-ink-muted">{{ __(ucfirst(str_replace('_', ' ', $attribution->attribution_model ?? $model))) }}</td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                @if ($attribution->is_primary)
                                    <x-ui.badge variant="success">{{ __('Primary') }}</x-ui.badge>
                                @else
                                    <span class="text-sm text-ink-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell text-sm text-ink-muted">{{ $attribution->attributed_at?->format('M j, Y g:i A') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        </x-workspace.widget>
    </x-layouts.entity-listing>
</x-app-layout>
