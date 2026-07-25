<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Competencies')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Competencies'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\Competency::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.performance.competencies.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            @csrf
            <select name="competency_category_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Category') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <x-forms.input name="name" placeholder="{{ __('Name') }}" required  />
            <x-forms.input name="code" placeholder="{{ __('Code') }}" required  />
            <x-forms.input name="description" placeholder="{{ __('Description') }}"  />
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Competency') }}</x-ui.button>
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Code') }}</th>
                    <th class="p-3 text-left">{{ __('Category') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($competencies as $competency)
                <tr class="border-t">
                    <td class="p-3">{{ $competency->name }}</td>
                    <td class="p-3">{{ $competency->code }}</td>
                    <td class="p-3">{{ $competency->category?->name }}</td>
                    <td class="p-3">
                        @can('delete', $competency)
                        <form method="POST" action="{{ route('hrms.performance.competencies.destroy', $competency) }}">@csrf @method('DELETE') <button class="text-red-600">{{ __('Delete') }}</button></form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $competencies->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
