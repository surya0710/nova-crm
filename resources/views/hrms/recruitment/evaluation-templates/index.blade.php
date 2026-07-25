<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Interview Templates')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Interview Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Templates') }}</h2>
            <ul class="space-y-2 text-sm">
                @forelse ($templates as $template)
                    <li><a href="{{ route('hrms.recruitment.evaluation-templates.show', $template) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $template->name }}</a>
                        @if ($template->department) — {{ $template->department->name }} @endif
                        @if (! $template->is_active) <span class="text-slate-400">({{ __('Inactive') }})</span> @endif
                    </li>
                @empty
                    <li class="text-slate-500">{{ __('No templates yet.') }}</li>
                @endforelse
            </ul>
            {{ $templates->links() }}
        </div>
        @can('create', App\Models\EvaluationTemplate::class)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
            <h2 class="font-medium mb-4">{{ __('Create Template') }}</h2>
            <form method="POST" action="{{ route('hrms.recruitment.evaluation-templates.store') }}" class="space-y-3">
                @csrf
                <div><label class="text-sm text-slate-600">{{ __('Name') }}</label><input name="name" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required></div>
                <div><label class="text-sm text-slate-600">{{ __('Department') }}</label>
                    <select name="department_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">{{ __('Any') }}</option>
                        @foreach ($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-sm text-slate-600">{{ __('Designation') }}</label>
                    <select name="designation_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500"><option value="">{{ __('Any') }}</option>
                        @foreach ($designations as $des)<option value="{{ $des->id }}">{{ $des->name }}</option>@endforeach
                    </select>
                </div>
                <fieldset class="border border-slate-200 rounded-md p-3">
                    <legend class="text-sm font-medium px-1">{{ __('Sample Section') }}</legend>
                    <input type="hidden" name="sections[0][title]" value="Core Competencies">
                    <input type="hidden" name="sections[0][weight]" value="1">
                    <input type="hidden" name="sections[0][questions][0][question]" value="Overall technical ability">
                    <input type="hidden" name="sections[0][questions][0][question_type]" value="rating_1_5">
                    <input type="hidden" name="sections[0][questions][0][is_required]" value="1">
                    <p class="text-xs text-slate-500">{{ __('Includes a default rating section. Edit after creation.') }}</p>
                </fieldset>
                <button class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">{{ __('Create Template') }}</button>
            </form>
        </div>
        @endcan
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
