<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$template->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Interview Templates'), 'href' => route('hrms.recruitment.evaluation-templates.index')],
                ['label' => $template->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Department') }}</dt><dd>{{ $template->department?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Designation') }}</dt><dd>{{ $template->designation?->name ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $template->is_active ? __('Active') : __('Inactive') }}</dd></div>
        </dl>
    </div>
    @foreach ($template->sections as $section)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-4">
            <h2 class="font-medium">{{ $section->title }} <span class="text-sm text-slate-400">({{ __('Weight') }}: {{ $section->weight }})</span></h2>
            <ul class="mt-3 space-y-2 text-sm">
                @foreach ($section->questions as $question)
                    <li>{{ $question->question }} — {{ $questionTypes[$question->question_type] ?? $question->question_type }}
                        @if ($question->is_required) <span class="text-red-500">*</span> @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
    @can('delete', $template)
    <form method="POST" action="{{ route('hrms.recruitment.evaluation-templates.destroy', $template) }}" onsubmit="return confirm('{{ __('Delete this template?') }}')">
        @csrf @method('DELETE')
        <button class="px-4 py-2 bg-red-600 text-white rounded-md">{{ __('Delete Template') }}</button>
    </form>
    @endcan
    </x-layouts.entity-detail>
</x-app-layout>
