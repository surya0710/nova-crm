<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Recruitment Analytics')"
        :subtitle="__('Funnel, sources, and recruiter performance')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Recruitment Analytics'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="flex flex-wrap gap-2 mb-4">
        @foreach ([
            'funnel' => __('Funnel'),
            'sources' => __('Sources'),
            'recruiters' => __('Recruiters'),
            'candidates' => __('Candidates'),
            'openings' => __('Openings'),
            'departments' => __('Departments'),
            'trends' => __('Trends'),
            'time' => __('Time'),
        ] as $key => $label)
            <a href="{{ route('hrms.recruitment.analytics', array_merge($filters, ['section' => $key])) }}"
               class="rounded-lg px-3 py-1.5 text-sm border {{ $section === $key ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @include('hrms.recruitment.partials.analytics-filters', [
        'action' => route('hrms.recruitment.analytics'),
        'filters' => $filters,
        'periods' => $periods,
        'extra' => ['section' => $section],
    ])

    @if ($section === 'funnel' && isset($funnel))
        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3 text-left">{{ __('Stage') }}</th>
                        <th class="p-3 text-left">{{ __('Count') }}</th>
                        <th class="p-3 text-left">{{ __('Conversion %') }}</th>
                        <th class="p-3 text-left">{{ __('Drop-off %') }}</th>
                        <th class="p-3 text-left">{{ __('Avg Days') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($funnel['stages'] as $stage)
                    <tr class="border-t">
                        <td class="p-3">{{ $stage['label'] }}</td>
                        <td class="p-3">{{ $stage['count'] }}</td>
                        <td class="p-3">{{ $stage['conversion_percent'] }}</td>
                        <td class="p-3">{{ $stage['drop_off_percent'] }}</td>
                        <td class="p-3">{{ $stage['average_duration_days'] ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($section === 'sources' && isset($sources))
        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3 text-left">{{ __('Source') }}</th>
                        <th class="p-3 text-left">{{ __('Applications') }}</th>
                        <th class="p-3 text-left">{{ __('Interviews') }}</th>
                        <th class="p-3 text-left">{{ __('Offers') }}</th>
                        <th class="p-3 text-left">{{ __('Hires') }}</th>
                        <th class="p-3 text-left">{{ __('Conversion %') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($sources as $row)
                    <tr class="border-t">
                        <td class="p-3">{{ $row['label'] }}</td>
                        <td class="p-3">{{ $row['applications'] }}</td>
                        <td class="p-3">{{ $row['interviews'] }}</td>
                        <td class="p-3">{{ $row['offers'] }}</td>
                        <td class="p-3">{{ $row['hires'] }}</td>
                        <td class="p-3">{{ $row['conversion_rate'] }}</td>
                    </tr>
                @empty
                    <tr class="border-t"><td class="p-3 text-slate-500" colspan="6">{{ __('No source data for this period.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @elseif ($section === 'recruiters' && isset($recruiters))
        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="p-3 text-left">{{ __('Recruiter') }}</th>
                        <th class="p-3 text-left">{{ __('Candidates') }}</th>
                        <th class="p-3 text-left">{{ __('Interviews') }}</th>
                        <th class="p-3 text-left">{{ __('Offers') }}</th>
                        <th class="p-3 text-left">{{ __('Accepted') }}</th>
                        <th class="p-3 text-left">{{ __('Hires') }}</th>
                        <th class="p-3 text-left">{{ __('Avg Hire Days') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($recruiters as $row)
                    <tr class="border-t">
                        <td class="p-3">{{ $row['recruiter_name'] }}</td>
                        <td class="p-3">{{ $row['candidates_handled'] }}</td>
                        <td class="p-3">{{ $row['interviews_scheduled'] }}</td>
                        <td class="p-3">{{ $row['offers_generated'] }}</td>
                        <td class="p-3">{{ $row['offers_accepted'] }}</td>
                        <td class="p-3">{{ $row['successful_hires'] }}</td>
                        <td class="p-3">{{ $row['average_hiring_time'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr class="border-t"><td class="p-3 text-slate-500" colspan="7">{{ __('No recruiter activity for this period.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @elseif ($section === 'candidates' && isset($candidates))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                <h2 class="font-medium mb-2">{{ __('Applications per Candidate') }}</h2>
                <p class="text-2xl font-semibold">{{ $candidates['applications_per_candidate'] }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                <h2 class="font-medium mb-2">{{ __('Salary Expectations') }}</h2>
                <p class="text-sm text-slate-600">{{ __('Avg') }}: {{ $candidates['salary_expectations']['average'] ?? '—' }}</p>
                <p class="text-sm text-slate-600">{{ __('Min') }}: {{ $candidates['salary_expectations']['min'] ?? '—' }}</p>
                <p class="text-sm text-slate-600">{{ __('Max') }}: {{ $candidates['salary_expectations']['max'] ?? '—' }}</p>
            </div>
            <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                <h2 class="font-medium mb-2">{{ __('Status Distribution') }}</h2>
                <ul class="text-sm space-y-1">
                    @foreach ($candidates['status_distribution'] as $stage => $total)
                        <li class="flex justify-between"><span>{{ $stage }}</span><span>{{ $total }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                <h2 class="font-medium mb-2">{{ __('Top Skills') }}</h2>
                <ul class="text-sm space-y-1">
                    @foreach ($candidates['skill_distribution'] as $skill => $total)
                        <li class="flex justify-between"><span>{{ $skill }}</span><span>{{ $total }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    @elseif ($section === 'openings' && isset($openings))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($openings as $key => $value)
                <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                    <p class="text-sm text-slate-500">{{ __(str_replace('_', ' ', ucfirst($key))) }}</p>
                    <p class="text-2xl font-semibold mt-1">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    @elseif ($section === 'departments' && isset($departments))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                <h2 class="font-medium mb-3">{{ __('Hiring by Department') }}</h2>
                <ul class="text-sm space-y-1">
                    @foreach ($departments['hiring_by_department'] as $row)
                        <li class="flex justify-between"><span>{{ $row['name'] }}</span><span>{{ $row['hires'] }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
                <h2 class="font-medium p-4 border-b">{{ __('Vacancy Aging') }}</h2>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50"><tr><th class="p-3 text-left">{{ __('Opening') }}</th><th class="p-3 text-left">{{ __('Age (days)') }}</th></tr></thead>
                    <tbody>
                    @foreach ($departments['vacancy_aging'] as $row)
                        <tr class="border-t"><td class="p-3">{{ $row['title'] }}</td><td class="p-3">{{ $row['age_days'] }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($section === 'trends' && isset($trends))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach (['hiring_trends' => __('Hiring Trends'), 'candidate_growth' => __('Candidate Growth'), 'offer_trends' => __('Offer Trends'), 'recruitment_volume' => __('Recruitment Volume')] as $key => $label)
                <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                    <h2 class="font-medium mb-3">{{ $label }}</h2>
                    <ul class="text-sm space-y-1 max-h-64 overflow-y-auto">
                        @foreach ($trends[$key] ?? [] as $point)
                            <li class="flex justify-between"><span>{{ $point['label'] }}</span><span>{{ $point['total'] }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @elseif ($section === 'time' && isset($time))
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            @foreach ($time as $key => $value)
                <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
                    <p class="text-sm text-slate-500">{{ __(str_replace('_', ' ', ucfirst($key))) }}</p>
                    <p class="text-2xl font-semibold mt-1">{{ $value ?? '—' }}</p>
                </div>
            @endforeach
        </div>
    @endif
    </x-layouts.entity-listing>
</x-app-layout>
