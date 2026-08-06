<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Interview Round')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Interview Rounds'), 'href' => route('hrms.recruitment.interview-rounds.index')],
                ['label' => __('Interview Round'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Candidate') }}</dt><dd>{{ $round->jobApplication?->candidate?->fullName() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Opening') }}</dt><dd>{{ $round->jobApplication?->jobOpening?->title }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Stage') }}</dt><dd>{{ $round->interviewStage?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $round->statusLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Scheduled') }}</dt><dd>{{ $round->scheduled_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Type') }}</dt><dd>{{ $round->interviewTypeLabel() }}</dd></div>
        </dl>
        <div class="mt-4 flex flex-wrap gap-2">
            @can('complete', $round)
                @if ($round->status !== 'completed')
                <form method="POST" action="{{ route('hrms.recruitment.interview-rounds.complete', $round) }}">@csrf<button class="px-3 py-1 bg-green-600 text-white rounded">{{ __('Complete') }}</button></form>
                @endif
            @endcan
            @can('cancel', $round)
                @if (! in_array($round->status, ['completed', 'cancelled']))
                <form method="POST" action="{{ route('hrms.recruitment.interview-rounds.cancel', $round) }}">@csrf<button class="px-3 py-1 bg-slate-600 text-white rounded">{{ __('Cancel') }}</button></form>
                @endif
            @endcan
            @can('create', App\Models\CandidateEvaluation::class)
                <a href="{{ route('hrms.recruitment.interview-rounds.evaluate', $round) }}" class="px-3 py-1 bg-indigo-600 text-white rounded inline-block">{{ __('Submit Evaluation') }}</a>
            @endcan
        </div>
    </div>
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <h2 class="font-medium mb-3">{{ __('Interviewers') }}</h2>
        <ul class="text-sm space-y-1">
            @foreach ($round->participants as $p)
                <li>{{ $p->displayName() }} — {{ $p->roleLabel() }}</li>
            @endforeach
        </ul>
    </div>
    @if ($round->evaluations->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <h2 class="font-medium mb-3">{{ __('Evaluations') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($round->evaluations as $eval)
                <li><a href="{{ route('hrms.recruitment.evaluations.show', $eval) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $eval->participant?->displayName() }}</a> — {{ $eval->recommendationLabel() }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
