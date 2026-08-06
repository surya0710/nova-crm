<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Feedback Reports')"
        :subtitle="__('Campaign-level feedback analytics')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Feedback'), 'href' => route('hrms.performance.feedback.index')],
                ['label' => __('Feedback Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Campaign') }}</th>
                    <th class="p-3 text-left">{{ __('Cycle') }}</th>
                    <th class="p-3 text-left">{{ __('Requests') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($campaigns as $campaign)
                <tr class="border-t">
                    <td class="p-3">{{ $campaign->name }}</td>
                    <td class="p-3">{{ $campaign->cycle?->name }}</td>
                    <td class="p-3">{{ $campaign->requests_count }}</td>
                    <td class="p-3">{{ $statuses[$campaign->status] ?? $campaign->status }}</td>
                    <td class="p-3">
                        <a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.feedback.reports.show', $campaign) }}">{{ __('View Report') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-slate-500">{{ __('No reports available.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $campaigns->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
