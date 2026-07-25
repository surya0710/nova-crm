<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Negotiation')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Negotiations'), 'href' => route('hrms.recruitment.negotiations.index')],
                ['label' => __('Negotiation'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Candidate') }}</dt><dd>{{ $negotiation->offerLetter?->candidate?->fullName() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Requested Salary') }}</dt><dd>{{ $negotiation->requested_salary ? number_format((float) $negotiation->requested_salary, 2) : '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Requested Joining Date') }}</dt><dd>{{ $negotiation->requested_joining_date?->format('Y-m-d') ?? '—' }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Outcome') }}</dt><dd>{{ $negotiation->outcomeLabel() }}</dd></div>
        </dl>
        @if ($negotiation->candidate_comments)<p class="text-sm mt-4"><strong>{{ __('Candidate') }}:</strong> {{ $negotiation->candidate_comments }}</p>@endif
        @if ($negotiation->recruiter_notes)<p class="text-sm mt-2"><strong>{{ __('Recruiter') }}:</strong> {{ $negotiation->recruiter_notes }}</p>@endif
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
