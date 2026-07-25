<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('360° Feedback')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('360° Feedback'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @if ($activeCampaign)
        <div class="rounded-xl bg-indigo-50 border border-indigo-200 p-4">
            <p class="text-sm text-indigo-600 font-medium">{{ __('Active Campaign') }}</p>
            <p class="text-lg font-semibold text-slate-900">{{ $activeCampaign->name }}</p>
            <p class="text-sm text-slate-600">{{ $activeCampaign->cycle?->name }}</p>
        </div>
        @endif
        @if ($pendingCount > 0)
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4">
            <p class="text-sm text-amber-600 font-medium">{{ __('Pending Feedback') }}</p>
            <p class="text-2xl font-semibold text-slate-900">{{ $pendingCount }}</p>
            <a class="text-sm text-indigo-600 hover:underline" href="{{ route('hrms.performance.feedback.my-feedback') }}">{{ __('View my requests') }}</a>
        </div>
        @endif
        @can('create', \App\Models\FeedbackCampaign::class)
        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 flex items-center">
            <a href="{{ route('hrms.performance.feedback.campaigns.index') }}" class="text-indigo-600 hover:underline font-medium">{{ __('Manage Campaigns') }}</a>
        </div>
        @endcan
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="font-medium text-slate-800">{{ __('Recent Campaigns') }}</h2>
            @can('viewAny', \App\Models\FeedbackCampaign::class)
            <a class="text-sm text-indigo-600 hover:underline" href="{{ route('hrms.performance.feedback.reports.index') }}">{{ __('Reports') }}</a>
            @endcan
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Cycle') }}</th>
                    <th class="p-3 text-left">{{ __('Due') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Participants') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($campaigns as $campaign)
                <tr class="border-t">
                    <td class="p-3">
                        <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.feedback.campaigns.show', $campaign) }}">{{ $campaign->name }}</a>
                    </td>
                    <td class="p-3">{{ $campaign->cycle?->name }}</td>
                    <td class="p-3">{{ $campaign->due_date?->format('Y-m-d') }}</td>
                    <td class="p-3">{{ $statuses[$campaign->status] ?? $campaign->status }}</td>
                    <td class="p-3">{{ $campaign->participants_count }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-slate-500">{{ __('No feedback campaigns yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
