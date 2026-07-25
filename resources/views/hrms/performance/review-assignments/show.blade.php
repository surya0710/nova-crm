<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Review Assignment')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Review Assignments'), 'href' => route('hrms.performance.review-assignments.index')],
                ['label' => __('Review Assignment'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-5 mb-6 space-y-3">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <div><span class="text-slate-500">{{ __('Employee') }}</span><div class="font-medium">{{ $assignment->employee?->first_name }} {{ $assignment->employee?->last_name }}</div></div>
            <div><span class="text-slate-500">{{ __('Type') }}</span><div class="font-medium">{{ $reviewTypes[$assignment->review_type] ?? $assignment->review_type }}</div></div>
            <div><span class="text-slate-500">{{ __('Status') }}</span><div class="font-medium">{{ $statuses[$assignment->status] ?? $assignment->status }}</div></div>
            <div><span class="text-slate-500">{{ __('Cycle') }}</span><div class="font-medium">{{ $assignment->cycle?->name }}</div></div>
            <div><span class="text-slate-500">{{ __('Template') }}</span><div class="font-medium">{{ $assignment->template?->name }}</div></div>
            <div><span class="text-slate-500">{{ __('Primary Reviewer') }}</span><div class="font-medium">{{ $assignment->primaryReviewer?->first_name }} {{ $assignment->primaryReviewer?->last_name }}</div></div>
            <div><span class="text-slate-500">{{ __('Due Date') }}</span><div class="font-medium">{{ $assignment->due_date?->format('Y-m-d') ?? '—' }}</div></div>
        </div>
        <div class="flex flex-wrap gap-3 pt-2">
            @if ($assignment->review)
                <a href="{{ route('hrms.performance.reviews.show', $assignment->review) }}" class="text-indigo-600 text-sm hover:underline">{{ __('Open Review') }}</a>
            @endif
            @can('activate', $assignment)
                @if ($assignment->status === 'planned')
                <form method="POST" action="{{ route('hrms.performance.review-assignments.activate', $assignment) }}">@csrf <x-ui.button type="submit" variant="primary" size="sm">{{ __('Activate') }}</x-ui.button></form>
                @endif
            @endcan
            @can('cancel', $assignment)
                @if ($assignment->isEditableLifecycle())
                <form method="POST" action="{{ route('hrms.performance.review-assignments.destroy', $assignment) }}">@csrf @method('DELETE') <button class="text-red-600 text-sm">{{ __('Cancel Assignment') }}</button></form>
                @endif
            @endcan
        </div>
    </div>

    @if ($assignment->review)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-5 text-sm">
        <h2 class="font-medium text-slate-800 mb-2">{{ __('Linked Review') }}</h2>
        <p>{{ __('Status') }}: {{ $reviewStatuses[$assignment->review->status] ?? $assignment->review->status }}</p>
        <p>{{ __('Submitted') }}: {{ $assignment->review->submitted_at?->format('Y-m-d H:i') ?? '—' }}</p>
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
