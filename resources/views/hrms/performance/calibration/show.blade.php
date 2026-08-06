<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$calibration->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Calibration'), 'href' => route('hrms.performance.calibration.index')],
                ['label' => $calibration->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl bg-white border p-4 mb-6 text-sm">
        <p><span class="text-slate-500">{{ __('Status') }}:</span> {{ $statuses[$calibration->status] ?? $calibration->status }}</p>
        <p><span class="text-slate-500">{{ __('Appraisal Session') }}:</span> {{ $calibration->session?->name }}</p>
        @if ($calibration->approved_at)
            <p><span class="text-slate-500">{{ __('Approved') }}:</span> {{ $calibration->approved_at->format('Y-m-d H:i') }}</p>
        @endif
    </div>

    @if (! $calibration->isCompleted())
    <div class="rounded-xl bg-white border p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Apply Rating Adjustments') }}</h2>
        <p class="text-sm text-slate-500 mb-3">{{ __('Original manager ratings are preserved. Calibrated ratings are stored separately.') }}</p>
        <form method="POST" action="{{ route('hrms.performance.calibration.adjustments', $calibration) }}">
            @csrf
            <div class="space-y-3">
                @foreach ($appraisals as $appraisal)
                <div class="grid grid-cols-1 md:grid-cols-5 gap-2 items-center border border-slate-100 rounded-lg p-3">
                    <span class="text-sm font-medium">{{ $appraisal->employee?->full_name }}</span>
                    <span class="text-sm text-slate-500">{{ __('Original') }}: {{ $appraisal->manager_rating ?? '—' }}</span>
                    <input type="hidden" name="adjustments[{{ $loop->index }}][employee_appraisal_id]" value="{{ $appraisal->id }}" />
                    <x-forms.input name="adjustments[{{ $loop->index }}][final_rating]" type="number" step="0.01" placeholder="{{ __('Calibrated Rating') }}" />
                    <input name="adjustments[{{ $loop->index }}][comments]" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Comments') }}" />
                </div>
                @endforeach
            </div>
            <div class="mt-4"><x-ui.button type="submit" variant="primary" size="sm">{{ __('Apply Adjustments') }}</x-ui.button></div>
        </form>
        <form method="POST" action="{{ route('hrms.performance.calibration.approve', $calibration) }}" class="mt-4">
            @csrf
            <textarea name="session_comments" rows="2" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('Session Comments') }}">{{ $calibration->session_comments }}</textarea>
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Approve Calibration') }}</x-ui.button>
        </form>
    </div>
    @endif

    @if ($calibration->adjustments)
    <div class="rounded-xl bg-white border overflow-hidden">
        <div class="px-4 py-3 border-b"><h2 class="font-medium">{{ __('Audit History') }}</h2></div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Appraisal ID') }}</th>
                    <th class="p-3 text-left">{{ __('Original') }}</th>
                    <th class="p-3 text-left">{{ __('Final') }}</th>
                    <th class="p-3 text-left">{{ __('Comments') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($calibration->adjustments as $adj)
                <tr class="border-t">
                    <td class="p-3">#{{ $adj['employee_appraisal_id'] }}</td>
                    <td class="p-3">{{ $adj['original_rating'] ?? '—' }}</td>
                    <td class="p-3">{{ $adj['final_rating'] ?? '—' }}</td>
                    <td class="p-3">{{ $adj['comments'] ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
