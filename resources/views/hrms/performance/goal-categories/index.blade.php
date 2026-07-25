<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Goal Categories')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Goal Categories'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\GoalCategory::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.performance.goal-categories.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            <x-forms.input name="name" placeholder="{{ __('Name') }}" required  />
            <x-forms.input name="code" placeholder="{{ __('Code') }}" required  />
            <x-forms.input name="description" placeholder="{{ __('Description') }}"  />
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Category') }}</x-ui.button>
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Code') }}</th>
                    <th class="p-3 text-left">{{ __('Active') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($categories as $category)
                <tr class="border-t">
                    <td class="p-3">{{ $category->name }}</td>
                    <td class="p-3">{{ $category->code }}</td>
                    <td class="p-3">{{ $category->is_active ? __('Yes') : __('No') }}</td>
                    <td class="p-3">
                        @can('delete', $category)
                        <form method="POST" action="{{ route('hrms.performance.goal-categories.destroy', $category) }}">@csrf @method('DELETE') <button class="text-red-600">{{ __('Delete') }}</button></form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $categories->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
