<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Performance Reviews')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Performance Reviews'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Employee') }}</th>
                    <th class="p-3 text-left">{{ __('Type') }}</th>
                    <th class="p-3 text-left">{{ __('Cycle') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Submitted') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($reviews as $review)
                <tr class="border-t">
                    <td class="p-3">{{ $review->employee?->first_name }} {{ $review->employee?->last_name }}</td>
                    <td class="p-3">{{ $reviewTypes[$review->review_type] ?? $review->review_type }}</td>
                    <td class="p-3">{{ $review->cycle?->name }}</td>
                    <td class="p-3">{{ $statuses[$review->status] ?? $review->status }}</td>
                    <td class="p-3">{{ $review->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="p-3"><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.reviews.show', $review) }}">{{ __('Open') }}</a></td>
                </tr>
            @empty
                <tr><td class="p-3 text-slate-500" colspan="6">{{ __('No reviews yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $reviews->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
