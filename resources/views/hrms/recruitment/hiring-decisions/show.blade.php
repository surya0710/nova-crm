<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Hiring Decision')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Hiring Decisions'), 'href' => route('hrms.recruitment.hiring-decisions.index')],
                ['label' => __('Hiring Decision'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Candidate') }}</dt><dd>{{ $decision->jobApplication?->candidate?->fullName() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Position') }}</dt><dd>{{ $decision->jobApplication?->jobOpening?->title }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Recommendation') }}</dt><dd>{{ $decision->recommendationLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Decision Date') }}</dt><dd>{{ $decision->decision_date?->format('Y-m-d') }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Decision By') }}</dt><dd>{{ $decision->decisionMaker?->name }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Onboarding Recommended') }}</dt><dd>{{ $decision->onboarding_recommended ? __('Yes') : __('No') }}</dd></div>
        </dl>
        @if ($decision->final_notes)<p class="text-sm mt-4 text-slate-600">{{ $decision->final_notes }}</p>@endif
        @if ($decision->onboarding_recommended)
        <p class="text-sm mt-4 text-green-700">{{ __('This candidate is recommended for HR onboarding. No employee record has been created.') }}</p>
        @endif
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
