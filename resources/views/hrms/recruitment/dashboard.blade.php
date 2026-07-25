<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Recruitment Dashboard')"
        :subtitle="__('Hire, track, and close roles')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Recruitment Dashboard'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if ($canAnalytics && $executive)
        @include('hrms.recruitment.partials.analytics-filters', [
            'action' => route('hrms.recruitment.dashboard'),
            'filters' => $filters,
            'periods' => $periods,
        ])

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
            @foreach ([
                'open_positions' => __('Open Positions'),
                'active_candidates' => __('Active Candidates'),
                'interviews_scheduled' => __('Interviews Scheduled'),
                'offers_pending' => __('Offers Pending'),
                'offers_accepted' => __('Offers Accepted'),
                'hiring_rate' => __('Hiring Rate %'),
                'time_to_hire' => __('Time to Hire (days)'),
                'time_to_fill' => __('Time to Fill (days)'),
                'offer_acceptance_rate' => __('Offer Acceptance %'),
                'applications_this_period' => __('Applications This Period'),
                'new_candidates' => __('New Candidates'),
                'active_recruiters' => __('Active Recruiters'),
            ] as $key => $label)
                <x-ui.stat-card
                    :label="$label"
                    :value="$executive['kpis'][$key] ?? '—'"
                />
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                <h2 class="font-medium text-slate-900 mb-3">{{ __('Hiring Manager Snapshot') }}</h2>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-slate-500">{{ __('Open Requisitions') }}</dt><dd class="font-semibold">{{ $executive['hiring_manager']['open_requisitions'] }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Pending Approvals') }}</dt><dd class="font-semibold">{{ $executive['hiring_manager']['pending_approvals'] }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Interview Completion %') }}</dt><dd class="font-semibold">{{ $executive['hiring_manager']['interview_completion_rate'] }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Avg Approval Days') }}</dt><dd class="font-semibold">{{ $executive['hiring_manager']['average_approval_time_days'] ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                <h2 class="font-medium text-slate-900 mb-3">{{ __('Time Metrics') }}</h2>
                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-slate-500">{{ __('Offer Approval Days') }}</dt><dd class="font-semibold">{{ $executive['time_metrics']['offer_approval_days'] ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Offer Acceptance Days') }}</dt><dd class="font-semibold">{{ $executive['time_metrics']['offer_acceptance_days'] ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Interview Minutes') }}</dt><dd class="font-semibold">{{ $executive['time_metrics']['interview_duration_minutes'] ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">{{ __('Hiring Cycle Days') }}</dt><dd class="font-semibold">{{ $executive['time_metrics']['average_hiring_cycle_days'] ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <p class="text-slate-600">{{ __('Recruitment platform with analytics, interview management, offers, and careers portal. Use the links below or the sidebar to navigate.') }}</p>
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @can('recruitment.analytics.view')
            <a href="{{ route('hrms.recruitment.analytics') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Analytics') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Funnel, sources, and recruiter performance') }}</p>
            </a>
            <a href="{{ route('hrms.recruitment.executive') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Executive Summary') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Leadership KPI overview') }}</p>
            </a>
            @endcan
            @can('recruitment.reports.view')
            <a href="{{ route('hrms.recruitment.reports.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Reports') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Executive and operational reports') }}</p>
            </a>
            <a href="{{ route('hrms.recruitment.saved-reports.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Saved Reports') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Reuse report configurations') }}</p>
            </a>
            @endcan
            @can('recruitment.reports.export')
            <a href="{{ route('hrms.recruitment.exports.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Exports') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('CSV and Excel downloads') }}</p>
            </a>
            @endcan
            <a href="{{ route('hrms.recruitment.requisitions.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Job Requisitions') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Internal hiring requests') }}</p>
            </a>
            <a href="{{ route('hrms.recruitment.openings.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Job Openings') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Published vacancies from approved requisitions') }}</p>
            </a>
            <a href="{{ route('hrms.recruitment.candidates.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Candidates') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Talent profiles independent of applications') }}</p>
            </a>
            <a href="{{ route('hrms.recruitment.applications.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Applications') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Candidate applications for openings') }}</p>
            </a>
            @can('recruitment.interview.view')
            <a href="{{ route('hrms.recruitment.interview-rounds.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Interview Rounds') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Schedule and track interviews') }}</p>
            </a>
            <a href="{{ route('hrms.recruitment.evaluation-templates.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Interview Templates') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Reusable evaluation scorecards') }}</p>
            </a>
            @endcan
            @can('recruitment.careers.manage')
            <a href="{{ route('hrms.recruitment.careers.settings.edit') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Careers Site') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Public careers page branding and content') }}</p>
            </a>
            @endcan
            @can('recruitment.portal.settings')
            <a href="{{ route('hrms.recruitment.portal.settings.edit') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Candidate Portal Settings') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Guest apply and portal access rules') }}</p>
            </a>
            @endcan
            @can('recruitment.portal.manage')
            <a href="{{ route('hrms.recruitment.portal.accounts.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <h2 class="font-medium text-slate-900">{{ __('Candidate Accounts') }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ __('Portal registrations separate from employees') }}</p>
            </a>
            @endcan
        </div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
