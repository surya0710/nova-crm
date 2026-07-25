<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="__('Feedback Report')"
        :subtitle="$campaign->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Feedback'), 'href' => route('hrms.performance.feedback.index')],
                ['label' => __('Reports'), 'href' => route('hrms.performance.feedback.reports.index')],
                ['label' => $campaign->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Overall Average') }}</p>
            <p class="text-2xl font-semibold">{{ $aggregation['overall_average'] ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Total Responses') }}</p>
            <p class="text-2xl font-semibold">{{ $aggregation['total_responses'] ?? 0 }}</p>
        </div>
        @if (isset($summary['participation_rate']))
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Participation Rate') }}</p>
            <p class="text-2xl font-semibold">{{ $summary['participation_rate'] }}%</p>
        </div>
        @endif
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <p class="text-sm text-slate-500">{{ __('Anonymous') }}</p>
            <p class="text-2xl font-semibold">{{ $campaign->is_anonymous ? __('Yes') : __('No') }}</p>
        </div>
    </div>

    @if (! empty($aggregation['by_participant_type']))
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium text-slate-800 mb-3">{{ __('By Participant Type') }}</h2>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Type') }}</th>
                    <th class="p-3 text-left">{{ __('Average') }}</th>
                    <th class="p-3 text-left">{{ __('Responses') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($aggregation['by_participant_type'] as $row)
                <tr class="border-t">
                    <td class="p-3">{{ $participantTypes[$row['participant_type']] ?? $row['participant_type'] }}</td>
                    <td class="p-3">{{ $row['average_rating'] }}</td>
                    <td class="p-3">{{ $row['response_count'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if (! empty($aggregation['by_competency']))
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium text-slate-800 mb-3">{{ __('Competency Breakdown') }}</h2>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Competency') }}</th>
                    <th class="p-3 text-left">{{ __('Average') }}</th>
                    <th class="p-3 text-left">{{ __('Responses') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($aggregation['by_competency'] as $row)
                <tr class="border-t">
                    <td class="p-3">{{ $row['question_text'] }}</td>
                    <td class="p-3">{{ $row['average_rating'] }}</td>
                    <td class="p-3">{{ $row['response_count'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if (! empty($summary['strengths']) || ! empty($summary['improvement_areas']))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if (! empty($summary['strengths']))
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h2 class="font-medium text-slate-800 mb-3">{{ __('Strengths') }}</h2>
            <ul class="list-disc pl-5 text-sm text-slate-600 space-y-1">
                @foreach ($summary['strengths'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        @if (! empty($summary['improvement_areas']))
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4">
            <h2 class="font-medium text-slate-800 mb-3">{{ __('Improvement Areas') }}</h2>
            <ul class="list-disc pl-5 text-sm text-slate-600 space-y-1">
                @foreach ($summary['improvement_areas'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
