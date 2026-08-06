<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$campaign->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Feedback'), 'href' => route('hrms.performance.feedback.index')],
                ['label' => __('Feedback Campaigns'), 'href' => route('hrms.performance.feedback.campaigns.index')],
                ['label' => $campaign->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 rounded-xl bg-white border border-slate-200 p-4">
            <h2 class="font-medium text-slate-800 mb-3">{{ __('Campaign Details') }}</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-slate-500">{{ __('Cycle') }}</dt><dd>{{ $campaign->cycle?->name }}</dd>
                <dt class="text-slate-500">{{ __('Template') }}</dt><dd>{{ $campaign->template?->name }}</dd>
                <dt class="text-slate-500">{{ __('Start') }}</dt><dd>{{ $campaign->start_date?->format('Y-m-d') }}</dd>
                <dt class="text-slate-500">{{ __('Due') }}</dt><dd>{{ $campaign->due_date?->format('Y-m-d') }}</dd>
                <dt class="text-slate-500">{{ __('Status') }}</dt><dd>{{ $statuses[$campaign->status] ?? $campaign->status }}</dd>
                <dt class="text-slate-500">{{ __('Anonymous') }}</dt><dd>{{ $campaign->is_anonymous ? __('Yes') : __('No') }}</dd>
            </dl>
            @if ($campaign->description)
                <p class="mt-3 text-sm text-slate-600">{{ $campaign->description }}</p>
            @endif

            @can('update', $campaign)
            <div class="mt-4 flex flex-wrap gap-2">
                @if (in_array($campaign->status, ['draft', 'scheduled']))
                <form method="POST" action="{{ route('hrms.performance.feedback.campaigns.activate', $campaign) }}">@csrf
                    <x-ui.button type="submit" variant="primary" size="sm" type="submit">{{ __('Activate') }}</x-ui.button>
                </form>
                @endif
                @if ($campaign->status === 'active')
                <form method="POST" action="{{ route('hrms.performance.feedback.campaigns.generate-requests', $campaign) }}">@csrf
                    <x-secondary-button type="submit">{{ __('Generate Requests') }}</x-secondary-button>
                </form>
                <form method="POST" action="{{ route('hrms.performance.feedback.campaigns.close', $campaign) }}">@csrf
                    <x-danger-button type="submit">{{ __('Close Campaign') }}</x-danger-button>
                </form>
                @endif
            </div>
            @endcan
        </div>

        @can('manageParticipants', $campaign)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h2 class="font-medium text-slate-800 mb-3">{{ __('Add Participant') }}</h2>
            <form method="POST" action="{{ route('hrms.performance.feedback.campaigns.participants.store', $campaign) }}" class="space-y-3">
                @csrf
                <select name="subject_employee_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    <option value="">{{ __('Subject Employee') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                    @endforeach
                </select>
                <select name="participant_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                    @foreach ($participantTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select name="participant_employee_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('Participant Employee') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                    @endforeach
                </select>
                <x-ui.button type="submit" variant="primary" size="sm" class="w-full">{{ __('Add Participant') }}</x-ui.button>
            </form>
        </div>
        @endcan
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b"><h2 class="font-medium text-slate-800">{{ __('Participants') }}</h2></div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Subject') }}</th>
                    <th class="p-3 text-left">{{ __('Participant') }}</th>
                    <th class="p-3 text-left">{{ __('Type') }}</th>
                    <th class="p-3 text-left">{{ __('Request Status') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($campaign->participants as $participant)
                <tr class="border-t">
                    <td class="p-3">{{ $participant->subjectEmployee?->first_name }} {{ $participant->subjectEmployee?->last_name }}</td>
                    <td class="p-3">
                        @if ($participant->participantEmployee)
                            {{ $participant->participantEmployee->first_name }} {{ $participant->participantEmployee->last_name }}
                        @else
                            {{ $participant->external_name }} ({{ $participant->external_email }})
                        @endif
                    </td>
                    <td class="p-3">{{ $participantTypes[$participant->participant_type] ?? $participant->participant_type }}</td>
                    <td class="p-3">{{ $participant->request ? ($requestStatuses[$participant->request->status] ?? $participant->request->status) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center text-slate-500">{{ __('No participants yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($campaign->summary)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
        <h2 class="font-medium text-slate-800 mb-3">{{ __('Summary') }}</h2>
        <p class="text-sm text-slate-600">{{ __('Participation rate') }}: {{ $campaign->summary['participation_rate'] ?? 0 }}%</p>
        @if (! empty($campaign->summary['overall_average']))
            <p class="text-sm text-slate-600">{{ __('Overall average') }}: {{ $campaign->summary['overall_average'] }}</p>
        @endif
        <a class="text-indigo-600 hover:underline text-sm" href="{{ route('hrms.performance.feedback.reports.show', $campaign) }}">{{ __('View full report') }}</a>
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
