<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Review Templates')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Review Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\PerformanceReviewTemplate::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.performance.templates.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <x-forms.input name="name" placeholder="{{ __('Name') }}" required  />
                <x-forms.input name="code" placeholder="{{ __('Code') }}" required  />
                <x-forms.input name="instructions" placeholder="{{ __('Instructions') }}"  />
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Template') }}</x-ui.button>
            </div>
            <input type="hidden" name="sections[0][key]" value="core" />
            <input type="hidden" name="sections[0][name]" value="Core Competencies" />
            <input type="hidden" name="sections[0][weightage]" value="100" />
            @if ($competencies->isNotEmpty())
                <div class="text-sm text-slate-600">
                    <p class="mb-2">{{ __('Attach competencies (optional):') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        @foreach ($competencies->take(6) as $index => $competency)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="competencies[{{ $index }}][competency_id]" value="{{ $competency->id }}" />
                                <input type="hidden" name="competencies[{{ $index }}][section_key]" value="core" />
                                <input type="hidden" name="competencies[{{ $index }}][weightage]" value="{{ round(100 / max(1, min(6, $competencies->count())), 2) }}" />
                                {{ $competency->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Code') }}</th>
                    <th class="p-3 text-left">{{ __('Sections') }}</th>
                    <th class="p-3 text-left">{{ __('Competencies') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($templates as $template)
                <tr class="border-t">
                    <td class="p-3">{{ $template->name }}</td>
                    <td class="p-3">{{ $template->code }}</td>
                    <td class="p-3">{{ $template->sections_count }}</td>
                    <td class="p-3">{{ $template->template_competencies_count }}</td>
                    <td class="p-3">
                        @can('delete', $template)
                        <form method="POST" action="{{ route('hrms.performance.templates.destroy', $template) }}">@csrf @method('DELETE') <button class="text-red-600">{{ __('Delete') }}</button></form>
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
