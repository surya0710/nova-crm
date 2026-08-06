@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [__('Title'), __('Audience'), __('Dates'), __('Active'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('HR Announcements')" :subtitle="__('Communicate updates to your workforce')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Announcements'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-4">
            <form method="POST" action="{{ route('hrms.announcements.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @csrf
                <x-forms.field :label="__('Title')" name="title" class="md:col-span-2">
                    <x-forms.input name="title" placeholder="{{ __('Title') }}" required />
                </x-forms.field>
                <x-forms.field :label="__('Content')" name="body" class="md:col-span-2">
                    <textarea name="body" class="w-full rounded-md border-line text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" rows="3" placeholder="{{ __('Content') }}" required></textarea>
                </x-forms.field>
                <x-forms.field :label="__('Target Audience')" name="target_audience">
                    <x-forms.select name="target_audience" required>
                        @foreach ($audiences as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Start Date')" name="start_date">
                    <x-forms.input type="date" name="start_date" />
                </x-forms.field>
                <x-forms.field :label="__('End Date')" name="end_date">
                    <x-forms.input type="date" name="end_date" />
                </x-forms.field>
                <label class="flex items-center gap-2 text-sm text-ink-heading">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-line" /> {{ __('Active') }}
                </label>
                <div class="flex items-end md:col-span-2">
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Announcement') }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @if ($announcements->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="generic" :title="__('No announcements yet.')" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($announcements as $announcement)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $announcement->title }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $audiences[$announcement->target_audience] ?? $announcement->target_audience }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $announcement->start_date?->format('M j') ?? '—' }} – {{ $announcement->end_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$announcement->is_active ? 'success' : 'neutral'">{{ $announcement->is_active ? __('Yes') : __('No') }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('hrms.announcements.destroy', $announcement) }}">
                                @csrf @method('DELETE')
                                <x-ui.button type="submit" variant="ghost" size="sm" class="text-danger">{{ __('Delete') }}</x-ui.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($announcements->hasPages())
            <x-slot:pagination>{{ $announcements->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
