<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Applications')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Applications'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\JobApplication::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <form method="POST" action="{{ route('hrms.recruitment.applications.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <select name="candidate_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Candidate') }}</option>
                @foreach ($candidates as $candidate)
                    <option value="{{ $candidate->id }}">{{ $candidate->fullName() }} ({{ $candidate->email }})</option>
                @endforeach
            </select>
            <select name="job_opening_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Published Opening') }}</option>
                @foreach ($openings as $opening)
                    <option value="{{ $opening->id }}">{{ $opening->title }}</option>
                @endforeach
            </select>
            <select name="source" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">{{ __('Source') }}</option>
                @foreach ($sources as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Submit Application') }}</x-ui.button>
        </form>
    </div>
    @endcan
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50"><tr><th class="p-3 text-left">{{ __('Candidate') }}</th><th class="p-3 text-left">{{ __('Opening') }}</th><th class="p-3 text-left">{{ __('Stage') }}</th><th class="p-3 text-left">{{ __('Applied') }}</th><th class="p-3 text-left">{{ __('Actions') }}</th></tr></thead>
            <tbody>
            @foreach ($applications as $application)
                <tr class="border-t">
                    <td class="p-3">{{ $application->candidate?->fullName() }}</td>
                    <td class="p-3">{{ $application->jobOpening?->title }}</td>
                    <td class="p-3">{{ $application->stageLabel() }}</td>
                    <td class="p-3">{{ $application->applied_date?->format('Y-m-d') }}</td>
                    <td class="p-3"><a href="{{ route('hrms.recruitment.applications.show', $application) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ __('View') }}</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $applications->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
