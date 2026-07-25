@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Name'), __('Code'), __('Address'), __('Manager'), __('Status'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Branches')"
        :subtitle="__('Managed under Organization Settings. Branches define locations for employees and holidays.')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Branches'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-4">
            <form method="POST" action="{{ route('hrms.branches.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-3">
                @csrf
                <x-forms.field :label="__('Name')" name="name">
                    <x-forms.input name="name" placeholder="{{ __('Name') }}" required />
                </x-forms.field>
                <x-forms.field :label="__('Code')" name="code">
                    <x-forms.input name="code" placeholder="{{ __('Code') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Branch Manager / Contact')" name="contact_person">
                    <x-forms.input name="contact_person" placeholder="{{ __('Branch Manager / Contact') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Address')" name="address_line_1" class="md:col-span-2">
                    <x-forms.input name="address_line_1" placeholder="{{ __('Address') }}" />
                </x-forms.field>
                <x-forms.field :label="__('City')" name="city">
                    <x-forms.input name="city" placeholder="{{ __('City') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Contact Email')" name="contact_email">
                    <x-forms.input name="contact_email" placeholder="{{ __('Contact Email') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Contact Phone')" name="contact_phone">
                    <x-forms.input name="contact_phone" placeholder="{{ __('Contact Phone') }}" />
                </x-forms.field>
                <label class="flex items-center gap-2 text-sm text-ink-heading">
                    <input type="checkbox" name="is_default" value="1" class="rounded border-line"> {{ __('Default Branch') }}
                </label>
                <label class="flex items-center gap-2 text-sm text-ink-heading">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-line"> {{ __('Active') }}
                </label>
                <div class="md:col-span-3">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Branch') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($branches->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="generic" :title="__('No branches yet.')" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($branches as $branch)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm text-ink-heading">
                            {{ $branch->name }}
                            @if ($branch->is_default)
                                <x-ui.badge variant="primary" class="ml-1">{{ __('Default') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $branch->code ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ collect([$branch->address_line_1, $branch->city])->filter()->implode(', ') ?: '—' }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $branch->contact_person ?? $branch->manager?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$branch->is_active ? 'success' : 'neutral'">{{ $branch->is_active ? __('Active') : __('Inactive') }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('hrms.branches.destroy', $branch) }}">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger">{{ __('Delete') }}</x-ui.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($branches->hasPages())
            <x-slot:pagination>{{ $branches->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
