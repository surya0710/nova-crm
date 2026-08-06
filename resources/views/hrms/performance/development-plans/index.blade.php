<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Development Plans')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Development Plans'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl bg-white border overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Employee') }}</th>
                    <th class="p-3 text-left">{{ __('Session') }}</th>
                    <th class="p-3 text-left">{{ __('Target Date') }}</th>
                    <th class="p-3 text-left"></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($appraisals as $appraisal)
                <tr class="border-t">
                    <td class="p-3">{{ $appraisal->employee?->full_name }}</td>
                    <td class="p-3">{{ $appraisal->session?->name }}</td>
                    <td class="p-3">{{ $appraisal->developmentPlan?->target_completion_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="p-3"><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.appraisals.show', $appraisal) }}">{{ __('View') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center text-slate-500">{{ __('No development plans found.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $appraisals->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
