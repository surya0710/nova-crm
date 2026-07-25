<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Talent Matrix')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Talent Matrix'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="GET" class="mb-6 flex gap-3">
        <select name="session_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" onchange="this.form.submit()">
            <option value="">{{ __('Select Session') }}</option>
            @foreach ($sessions as $s)
                <option value="{{ $s->id }}" @selected($session?->id === $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </form>

    @if ($session)
    <div class="rounded-xl bg-white border p-4 mb-6">
        <h2 class="font-medium mb-4">{{ $matrix['config']['performance_axis'] ?? 'Performance' }} × {{ $matrix['config']['potential_axis'] ?? 'Potential' }}</h2>
        <div class="grid grid-cols-3 gap-2">
            @for ($potential = 3; $potential >= 1; $potential--)
                @for ($performance = 1; $performance <= 3; $performance++)
                    @php $key = "{$performance}-{$potential}"; $cell = $matrix['cells'][$key] ?? collect(); @endphp
                    <div class="border border-slate-200 rounded-lg p-3 min-h-[100px] bg-slate-50">
                        <p class="text-xs font-medium text-slate-500 mb-2">{{ $matrix['config']['classifications'][$key] ?? $key }}</p>
                        @foreach ($cell as $entry)
                            <p class="text-sm">{{ $entry->employee?->full_name }}</p>
                        @endforeach
                    </div>
                @endfor
            @endfor
        </div>
    </div>

    @if (auth()->user()?->hasPermission('performance.talent.manage'))
    <div class="rounded-xl bg-white border p-4">
        <h2 class="font-medium mb-3">{{ __('Classify Employee') }}</h2>
        <form method="POST" action="{{ route('hrms.performance.talent-matrix.classify') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            @csrf
            <select name="employee_appraisal_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Employee Appraisal') }}</option>
                @foreach ($matrix['entries'] as $entry)
                    <option value="{{ $entry->employee_appraisal_id }}">{{ $entry->employee?->full_name }}</option>
                @endforeach
                @foreach (\App\Models\EmployeeAppraisal::query()->where('appraisal_session_id', $session->id)->with('employee')->get() as $appraisal)
                    @if (! $matrix['entries']->contains('employee_appraisal_id', $appraisal->id))
                        <option value="{{ $appraisal->id }}">{{ $appraisal->employee?->full_name }}</option>
                    @endif
                @endforeach
            </select>
            <x-forms.input name="performance_band" type="number" min="1" max="3" placeholder="{{ __('Performance (1-3)') }}" required  />
            <x-forms.input name="potential_band" type="number" min="1" max="3" placeholder="{{ __('Potential (1-3)') }}" required  />
            <x-ui.button type="submit" variant="primary" size="sm">{{ __('Classify') }}</x-ui.button>
        </form>
    </div>
    @endcan
    @else
    <p class="text-slate-500">{{ __('Select an appraisal session to view the talent matrix.') }}</p>
    @endif
    </x-layouts.entity-listing>
</x-app-layout>
