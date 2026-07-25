<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Application Details')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Applications'), 'href' => route('hrms.recruitment.applications.index')],
                ['label' => __('Application Details'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">{{ __('Candidate') }}</dt><dd>{{ $application->candidate?->fullName() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Opening') }}</dt><dd>{{ $application->jobOpening?->title }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Stage') }}</dt><dd>{{ $application->stageLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $application->statusLabel() }}</dd></div>
            <div><dt class="text-slate-500">{{ __('Applied Date') }}</dt><dd>{{ $application->applied_date?->format('Y-m-d') }}</dd></div>
        </dl>
        @can('update', $application)
        <form method="POST" action="{{ route('hrms.recruitment.applications.stage', $application) }}" class="mt-4 flex gap-2">
            @csrf
            <select name="stage" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach ($stages as $value => $label)
                    <option value="{{ $value }}" @selected($application->stage === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700">{{ __('Update Stage') }}</button>
        </form>
        @endcan
    </div>
    </x-layouts.entity-detail>
</x-app-layout>
