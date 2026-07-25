@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Code'), __('Name'), __('Category'), __('Assigned To'), __('Status'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Assets')" :subtitle="__('Track company assets and assignments')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Assets'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-4">
            <form method="POST" action="{{ route('hrms.assets.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                @csrf
                <x-forms.field :label="__('Name')" name="name">
                    <x-forms.input name="name" placeholder="{{ __('Name') }}" required />
                </x-forms.field>
                <x-forms.field :label="__('Category')" name="category">
                    <x-forms.select name="category" required>
                        <option value="">{{ __('Category') }}</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Serial Number')" name="serial_number">
                    <x-forms.input name="serial_number" placeholder="{{ __('Serial Number') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Asset Code (auto)')" name="asset_code">
                    <x-forms.input name="asset_code" placeholder="{{ __('Asset Code (auto)') }}" />
                </x-forms.field>
                <div class="flex items-end">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Asset') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-slot:filters>
            <div class="flex flex-wrap gap-2">
                <x-ui.button :href="route('hrms.assets.index')" :variant="! request('status') ? 'primary' : 'secondary'" size="sm">{{ __('All') }}</x-ui.button>
                @foreach ($statuses as $key => $label)
                    <x-ui.button :href="route('hrms.assets.index', ['status' => $key])" :variant="request('status') === $key ? 'primary' : 'secondary'" size="sm">{{ $label }}</x-ui.button>
                @endforeach
            </div>
        </x-slot:filters>

        @if ($assets->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="assets" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($assets as $asset)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $asset->asset_code }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('hrms.assets.show', $asset) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $asset->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $categories[$asset->category] ?? $asset->category }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $asset->employee?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge variant="neutral">{{ $statuses[$asset->status] ?? $asset->status }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.button :href="route('hrms.assets.show', $asset)" variant="link" size="sm">{{ __('View') }}</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($assets->hasPages())
            <x-slot:pagination>{{ $assets->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
