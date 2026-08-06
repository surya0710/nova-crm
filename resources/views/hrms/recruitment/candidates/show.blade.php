<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$candidate->fullName()"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Candidates'), 'href' => route('hrms.recruitment.candidates.index')],
                ['label' => $candidate->fullName(), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Email') }}</dt><dd>{{ $candidate->email }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Phone') }}</dt><dd>{{ $candidate->phone ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Current Company') }}</dt><dd>{{ $candidate->current_company ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Source') }}</dt><dd>{{ $candidate->sourceLabel() }}</dd></div>
        </dl>
    </div>
    @if ($candidate->applications->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <h2 class="font-medium mb-3">{{ __('Applications') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($candidate->applications as $application)
                <li>
                    <a href="{{ route('hrms.recruitment.applications.show', $application) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $application->jobOpening?->title }}</a>
                    — {{ $application->stageLabel() }}
                    @if ($application->interviewRounds->isNotEmpty())
                        · {{ __('Current interview stage') }}: {{ $application->interviewRounds->first()?->interviewStage?->name }}
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    @if ($interviewRounds->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <h2 class="font-medium mb-3">{{ __('Interview History') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($interviewRounds as $round)
                <li>
                    <a href="{{ route('hrms.recruitment.interview-rounds.show', $round) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $round->interviewStage?->name }}</a>
                    — {{ $round->statusLabel() }} · {{ $round->scheduled_at?->format('Y-m-d') ?? __('Not scheduled') }}
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    @if ($evaluations->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <h2 class="font-medium mb-3">{{ __('Evaluation Summaries') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($evaluations as $evaluation)
                <li>
                    <a href="{{ route('hrms.recruitment.evaluations.show', $evaluation) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $evaluation->interviewRound?->interviewStage?->name }}</a>
                    — {{ $evaluation->participant?->displayName() }}: {{ $evaluation->recommendationLabel() }}
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    @if ($offerLetters->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <h2 class="font-medium mb-3">{{ __('Offer History') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($offerLetters as $offer)
                <li>
                    <a href="{{ route('hrms.recruitment.offers.show', $offer) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $offer->jobApplication?->jobOpening?->title }}</a>
                    — {{ $offer->statusLabel() }} · {{ number_format((float) $offer->proposed_salary, 2) }}
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    @if ($offerLetters->flatMap->negotiations->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <h2 class="font-medium mb-3">{{ __('Negotiation Timeline') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($offerLetters->flatMap->negotiations->sortByDesc('created_at') as $negotiation)
                <li>
                    <a href="{{ route('hrms.recruitment.negotiations.show', $negotiation) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $negotiation->created_at?->format('Y-m-d') }}</a>
                    — {{ $negotiation->outcomeLabel() }}
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    @if ($offerLetters->flatMap->approvals->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <h2 class="font-medium mb-3">{{ __('Approval Status') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($offerLetters->flatMap->approvals as $approval)
                <li>{{ $approval->approver?->name }} — {{ $approval->statusLabel() }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    @if ($hiringDecisions->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <h2 class="font-medium mb-3">{{ __('Hiring Decisions') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($hiringDecisions as $decision)
                <li>
                    <a href="{{ route('hrms.recruitment.hiring-decisions.show', $decision) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $decision->jobApplication?->jobOpening?->title }}</a>
                    — {{ $decision->recommendationLabel() }}
                    @if ($decision->onboarding_recommended) · {{ __('Onboarding recommended') }} @endif
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
