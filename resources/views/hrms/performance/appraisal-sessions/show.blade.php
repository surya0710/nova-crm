<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$session->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Appraisal Sessions'), 'href' => route('hrms.performance.appraisal-sessions.index')],
                ['label' => $session->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="flex flex-wrap gap-2 mb-6">
        @if (in_array($session->status, ['draft', 'scheduled']))
            <form method="POST" action="{{ route('hrms.performance.appraisal-sessions.activate', $session) }}">@csrf<button class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg">{{ __('Activate') }}</button></form>
        @endif
        @if ($session->status === 'active')
            <form method="POST" action="{{ route('hrms.performance.appraisal-sessions.generate', $session) }}">@csrf<button class="px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-lg">{{ __('Generate Appraisals') }}</button></form>
            <form method="POST" action="{{ route('hrms.performance.appraisal-sessions.close', $session) }}">@csrf<button class="px-3 py-1.5 bg-amber-600 text-white text-sm rounded-lg">{{ __('Close Session') }}</button></form>
        @endif
        @if ($session->status === 'closed')
            <form method="POST" action="{{ route('hrms.performance.appraisal-sessions.archive', $session) }}">@csrf<button class="px-3 py-1.5 bg-slate-600 text-white text-sm rounded-lg">{{ __('Archive') }}</button></form>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-sm">
        <div class="rounded-xl bg-white border p-4"><span class="text-slate-500">{{ __('Status') }}</span><p class="font-semibold">{{ $statuses[$session->status] ?? $session->status }}</p></div>
        <div class="rounded-xl bg-white border p-4"><span class="text-slate-500">{{ __('Cycle') }}</span><p class="font-semibold">{{ $session->cycle?->name }}</p></div>
        <div class="rounded-xl bg-white border p-4"><span class="text-slate-500">{{ __('Appraisals') }}</span><p class="font-semibold">{{ $session->employeeAppraisals->count() }}</p></div>
    </div>

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 border-b"><h2 class="font-medium">{{ __('Employee Appraisals') }}</h2></div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Employee') }}</th>
                    <th class="p-3 text-left">{{ __('Manager Rating') }}</th>
                    <th class="p-3 text-left">{{ __('Final Rating') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left"></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($session->employeeAppraisals as $appraisal)
                <tr class="border-t">
                    <td class="p-3">{{ $appraisal->employee?->full_name }}</td>
                    <td class="p-3">{{ $appraisal->manager_rating ?? '—' }}</td>
                    <td class="p-3">{{ $appraisal->final_rating ?? '—' }}</td>
                    <td class="p-3">{{ $appraisalStatuses[$appraisal->status] ?? $appraisal->status }}</td>
                    <td class="p-3"><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.appraisals.show', $appraisal) }}">{{ __('View') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-slate-500">{{ __('No appraisals generated yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($session->calibrations->isNotEmpty())
    <div class="rounded-xl bg-white border p-4">
        <h2 class="font-medium mb-2">{{ __('Calibration Sessions') }}</h2>
        <ul class="text-sm space-y-1">
            @foreach ($session->calibrations as $calibration)
                <li><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.calibration.show', $calibration) }}">{{ $calibration->name }}</a> — {{ $calibration->status }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    </x-layouts.entity-detail>
</x-app-layout>
