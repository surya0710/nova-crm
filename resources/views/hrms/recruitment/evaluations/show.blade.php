<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Evaluation Details')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Evaluations'), 'href' => route('hrms.recruitment.evaluations.index')],
                ['label' => __('Evaluation Details'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6 mb-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Candidate') }}</dt><dd>{{ $evaluation->interviewRound?->jobApplication?->candidate?->fullName() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Interviewer') }}</dt><dd>{{ $evaluation->participant?->displayName() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Overall Rating') }}</dt><dd>{{ $evaluation->overall_rating ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Recommendation') }}</dt><dd>{{ $evaluation->recommendationLabel() }}</dd></div>
        </dl>
        @if ($evaluation->strengths)<p class="mt-4 text-sm"><strong>{{ __('Strengths') }}:</strong> {{ $evaluation->strengths }}</p>@endif
        @if ($evaluation->concerns)<p class="mt-2 text-sm"><strong>{{ __('Concerns') }}:</strong> {{ $evaluation->concerns }}</p>@endif
        @if ($evaluation->summary)<p class="mt-2 text-sm"><strong>{{ __('Summary') }}:</strong> {{ $evaluation->summary }}</p>@endif
    </div>
    @if ($evaluation->responses->isNotEmpty())
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <h2 class="font-medium mb-3">{{ __('Scorecard Responses') }}</h2>
        <ul class="text-sm space-y-2">
            @foreach ($evaluation->responses as $response)
                <li><span class="text-slate-500">{{ $response->question?->question }}:</span> {{ $response->response_value }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
