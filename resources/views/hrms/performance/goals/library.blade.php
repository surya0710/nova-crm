<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Goal Library')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Goal Library'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\GoalTemplate::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.performance.goals.library.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <x-forms.input name="title" placeholder="{{ __('Title') }}" required  />
            <select name="goal_category_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('Category (optional)') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="goal_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($goalTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select name="measurement_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                @foreach ($measurementTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <x-forms.input name="default_weight" type="number" step="0.01" placeholder="{{ __('Default Weight') }}" value="20"  />
            <x-forms.input name="description" placeholder="{{ __('Description') }}"  />
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Template') }}</x-ui.button>
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Title') }}</th>
                    <th class="p-3 text-left">{{ __('Category') }}</th>
                    <th class="p-3 text-left">{{ __('Type') }}</th>
                    <th class="p-3 text-left">{{ __('Measurement') }}</th>
                    <th class="p-3 text-left">{{ __('Weight') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($templates as $template)
                <tr class="border-t">
                    <td class="p-3">{{ $template->title }}</td>
                    <td class="p-3">{{ $template->category?->name ?? '—' }}</td>
                    <td class="p-3">{{ $goalTypes[$template->goal_type] ?? $template->goal_type }}</td>
                    <td class="p-3">{{ $measurementTypes[$template->measurement_type] ?? $template->measurement_type }}</td>
                    <td class="p-3">{{ $template->default_weight }}%</td>
                    <td class="p-3">
                        @can('delete', $template)
                        <form method="POST" action="{{ route('hrms.performance.goals.library.destroy', $template) }}">@csrf @method('DELETE') <button class="text-red-600">{{ __('Delete') }}</button></form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $templates->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
