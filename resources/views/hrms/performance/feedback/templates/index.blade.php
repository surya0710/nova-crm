<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Feedback Templates')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Feedback'), 'href' => route('hrms.performance.feedback.index')],
                ['label' => __('Feedback Templates'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\FeedbackTemplate::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium text-slate-800 mb-3">{{ __('Create Template') }}</h2>
        <form method="POST" action="{{ route('hrms.performance.feedback.templates.store') }}" class="space-y-3">
            @csrf
            <x-forms.input name="name" placeholder="{{ __('Template Name') }}" :value="old('name')" required class="w-full"  />
            <textarea name="description" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Description') }}">{{ old('description') }}</textarea>
            <div class="border rounded-md p-3 space-y-2">
                <p class="text-sm font-medium text-slate-700">{{ __('Default Questions') }}</p>
                <input type="hidden" name="questions[0][question_type]" value="rating" />
                <input type="text" name="questions[0][question_text]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Rating question') }}" value="{{ old('questions.0.question_text', 'Overall performance rating') }}" required />
                <input type="hidden" name="questions[0][scale_min]" value="1" />
                <input type="hidden" name="questions[0][scale_max]" value="5" />
                <input type="hidden" name="questions[1][question_type]" value="text" />
                <input type="text" name="questions[1][question_text]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Text question') }}" value="{{ old('questions.1.question_text', 'What are this person\'s key strengths?') }}" required />
                <input type="hidden" name="questions[2][question_type]" value="text" />
                <input type="text" name="questions[2][question_text]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Text question') }}" value="{{ old('questions.2.question_text', 'What areas could this person improve?') }}" required />
            </div>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Template') }}</x-ui.button>
        </form>
    </div>
    @endcan

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Questions') }}</th>
                    <th class="p-3 text-left">{{ __('Active') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($templates as $template)
                <tr class="border-t">
                    <td class="p-3">{{ $template->name }}</td>
                    <td class="p-3">{{ $template->questions_count }}</td>
                    <td class="p-3">{{ $template->is_active ? __('Yes') : __('No') }}</td>
                    <td class="p-3">
                        <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.feedback.templates.show', $template) }}">{{ __('View') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center text-slate-500">{{ __('No templates found.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $templates->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
