<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$template->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Feedback'), 'href' => route('hrms.performance.feedback.index')],
                ['label' => __('Feedback Templates'), 'href' => route('hrms.performance.feedback.templates.index')],
                ['label' => $template->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-entity.section :title="__('Questions')">
            @if ($template->description)
                <p class="text-sm text-ink-muted mb-4">{{ $template->description }}</p>
            @endif
            <ol class="list-decimal pl-5 space-y-3 text-sm">
                @foreach ($template->questions as $question)
                    <li>
                        <span class="font-medium text-ink-heading">{{ $question->question_text }}</span>
                        <span class="text-ink-muted">({{ $questionTypes[$question->question_type] ?? $question->question_type }})</span>
                        @if ($question->is_required)
                            <x-ui.badge variant="danger" class="ml-1">{{ __('Required') }}</x-ui.badge>
                        @endif
                    </li>
                @endforeach
            </ol>
        </x-entity.section>
    </x-layouts.entity-detail>
</x-app-layout>
