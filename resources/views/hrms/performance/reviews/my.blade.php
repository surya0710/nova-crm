<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('My Reviews')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('My Reviews'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if (! $employee)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">{{ __('No employee profile is linked to your user account.') }}</div>
    @else
    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Cycle') }}</th>
                    <th class="p-3 text-left">{{ __('Template') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Due') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($reviews as $review)
                <tr class="border-t">
                    <td class="p-3">{{ $review->cycle?->name }}</td>
                    <td class="p-3">{{ $review->template?->name }}</td>
                    <td class="p-3">{{ $statuses[$review->status] ?? $review->status }}</td>
                    <td class="p-3">{{ $review->assignment?->due_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="p-3"><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.reviews.show', $review) }}">{{ $review->isEditable() ? __('Continue') : __('View') }}</a></td>
                </tr>
            @empty
                <tr><td class="p-3 text-slate-500" colspan="5">{{ __('No self reviews assigned.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $reviews->links() }}</div>
    @endif
    </x-layouts.entity-listing>
</x-app-layout>
