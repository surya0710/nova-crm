@php $canCreate = auth('platform')->user()->hasPermission('platform.industry_templates.create'); @endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Industry Templates')"
        :subtitle="__('Published templates for new organization provisioning')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Industry Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if ($canCreate)
                <x-ui.button :href="route('platform.industry-templates.create')" variant="primary" size="sm">{{ __('New Template') }}</x-ui.button>
            @endif
        </x-slot:actions>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-5">
                <x-forms.field :label="__('Search')" name="search" class="sm:col-span-2">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Search templates…') }}" />
                </x-forms.field>
                <x-forms.field :label="__('Status')" name="status">
                    <x-forms.select name="status">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <x-forms.field :label="__('Visibility')" name="visibility">
                    <x-forms.select name="visibility">
                        <option value="">{{ __('All visibility') }}</option>
                        @foreach ($visibilities as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['visibility'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($templates->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="generic" :title="__('No industry templates yet')" :action-href="$canCreate ? route('platform.industry-templates.create') : null" action-label="{{ __('New Template') }}" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Template') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Industry') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium md:table-cell">{{ __('Visibility') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium lg:table-cell">{{ __('Current Version') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium lg:table-cell">{{ __('Applications') }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($templates as $template)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-ink-heading">{{ $template->name }}</div>
                                        <div class="text-xs text-ink-muted">{{ $template->slug }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $template->industry ?? '—' }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $template->statusLabel() }}</td>
                                    <td class="hidden px-4 py-3 text-ink-muted md:table-cell">{{ $template->visibilityLabel() }}</td>
                                    <td class="hidden px-4 py-3 text-ink-muted lg:table-cell">{{ $template->currentVersion ? 'v'.$template->currentVersion->version : '—' }}</td>
                                    <td class="hidden px-4 py-3 text-ink-muted lg:table-cell">{{ $template->applications_count }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <x-ui.button :href="route('platform.industry-templates.show', $template)" variant="ghost" size="sm">{{ __('View') }}</x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $templates->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
