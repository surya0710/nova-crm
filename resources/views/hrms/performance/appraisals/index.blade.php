<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Appraisal Dashboard')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Appraisal Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <a href="{{ route('hrms.performance.appraisal-sessions.index') }}" class="rounded-xl border border-line bg-surface-card shadow-sm p-4 hover:border-slate-300">
            <p class="text-sm text-slate-500">{{ __('Sessions') }}</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $sessionCount }}</p>
        </a>
        <a href="{{ route('hrms.performance.appraisals.list') }}" class="rounded-xl border border-line bg-surface-card shadow-sm p-4 hover:border-slate-300">
            <p class="text-sm text-slate-500">{{ __('Appraisals') }}</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $appraisalCount }}</p>
        </a>
        <a href="{{ route('hrms.performance.calibration.index') }}" class="rounded-xl border border-line bg-surface-card shadow-sm p-4 hover:border-slate-300">
            <p class="text-sm text-slate-500">{{ __('Calibrations') }}</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $calibrationCount }}</p>
        </a>
        <a href="{{ route('hrms.performance.talent-matrix.index') }}" class="rounded-xl border border-line bg-surface-card shadow-sm p-4 hover:border-slate-300">
            <p class="text-sm text-slate-500">{{ __('Talent Matrix') }}</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $talentCount }}</p>
        </a>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100"><h2 class="font-medium">{{ __('Active Sessions') }}</h2></div>
            <table class="min-w-full text-sm">
                @forelse ($activeSessions as $session)
                    <tr class="border-t">
                        <td class="p-3"><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.appraisal-sessions.show', $session) }}">{{ $session->name }}</a></td>
                        <td class="p-3">{{ $session->start_date->toDateString() }} – {{ $session->end_date->toDateString() }}</td>
                    </tr>
                @empty
                    <tr><td class="p-3 text-slate-500" colspan="2">{{ __('No active sessions.') }}</td></tr>
                @endforelse
            </table>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100"><h2 class="font-medium">{{ __('Recent Appraisals') }}</h2></div>
            <table class="min-w-full text-sm">
                @forelse ($recentAppraisals as $appraisal)
                    <tr class="border-t">
                        <td class="p-3">{{ $appraisal->employee?->full_name }}</td>
                        <td class="p-3">{{ $appraisal->final_rating ?? '—' }}</td>
                        <td class="p-3"><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.appraisals.show', $appraisal) }}">{{ __('View') }}</a></td>
                    </tr>
                @empty
                    <tr><td class="p-3 text-slate-500" colspan="3">{{ __('No appraisals yet.') }}</td></tr>
                @endforelse
            </table>
        </div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
