<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Evaluations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Evaluations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-500 border-b"><th class="pb-2">{{ __('Candidate') }}</th><th class="pb-2">{{ __('Interviewer') }}</th><th class="pb-2">{{ __('Recommendation') }}</th><th class="pb-2">{{ __('Status') }}</th></tr></thead>
            <tbody>
                @forelse ($evaluations as $evaluation)
                    <tr class="border-b border-slate-100">
                        <td class="py-2"><a href="{{ route('hrms.recruitment.evaluations.show', $evaluation) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $evaluation->interviewRound?->jobApplication?->candidate?->fullName() }}</a></td>
                        <td class="py-2">{{ $evaluation->participant?->displayName() }}</td>
                        <td class="py-2">{{ $evaluation->recommendationLabel() }}</td>
                        <td class="py-2">{{ $evaluation->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-slate-500">{{ __('No evaluations yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $evaluations->links() }}
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
