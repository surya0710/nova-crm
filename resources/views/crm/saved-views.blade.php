<x-app-layout>
    <x-layouts.entity-listing
        :title="__('Saved views')"
        :subtitle="__('Reusable CRM filters')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => __('Saved views'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if ($views->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="saved_views"
                    :action-href="route('leads.index')"
                    :action-label="__('Browse leads')"
                >
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        @if (auth()->user()->hasPermission('leads.view'))
                            <x-ui.badge variant="primary">{{ crm_term('leads') }}</x-ui.badge>
                        @endif
                        @if (auth()->user()->hasPermission('customers.view'))
                            <x-ui.badge variant="info">{{ crm_term('customers') }}</x-ui.badge>
                        @endif
                        @if (auth()->user()->hasPermission('opportunities.view'))
                            <x-ui.badge variant="success">{{ crm_term('pipeline') }}</x-ui.badge>
                        @endif
                    </div>
                </x-ui.empty-state-preset>
            </x-ui.card>
        @else
            <x-tables.table :columns="[__('Name'), __('Entity'), __('Visibility'), ['label' => __('Open'), 'align' => 'right']]">
                @foreach ($views as $row)
                    <tr class="hover:bg-surface-muted/50">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">
                            <div class="flex items-center gap-2">
                                <span>{{ $row['filter']->name }}</span>
                                @if ($row['is_default'] ?? false)
                                    <x-ui.badge variant="primary">{{ __('Default') }}</x-ui.badge>
                                @endif
                                @if ($row['filter']->validation_status !== 'valid')
                                    <x-ui.badge variant="warning">{{ __('Needs review') }}</x-ui.badge>
                                @endif
                            </div>
                            @if ($row['filter']->description)
                                <p class="mt-0.5 text-xs text-ink-muted">{{ $row['filter']->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <x-ui.badge variant="neutral">{{ ucfirst($row['entity']) }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <x-ui.badge :variant="$row['filter']->isShared() ? 'info' : 'neutral'">
                                {{ $row['filter']->isShared() ? __('Shared') : __('Private') }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.button :href="$row['href']" variant="secondary" size="sm">{{ __('Open') }}</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
