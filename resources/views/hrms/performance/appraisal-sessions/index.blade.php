<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Appraisal Sessions')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Appraisal Sessions'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\AppraisalSession::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Create Session') }}</h2>
        <form method="POST" action="{{ route('hrms.performance.appraisal-sessions.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <x-forms.input name="name" placeholder="{{ __('Session Name') }}" required  />
            <select name="performance_cycle_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Performance Cycle') }}</option>
                @foreach ($cycles as $cycle)
                    <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                @endforeach
            </select>
            <x-forms.input name="start_date" type="date" required  />
            <x-forms.input name="end_date" type="date" required  />
            @foreach ($defaultWeights as $key => $weight)
                <x-forms.input name="rating_weights[{{ $key }}]" type="number" step="0.01" :value="$weight" placeholder="{{ ucfirst(str_replace('_', ' ', $key)) }} %"  />
            @endforeach
            <div class="md:col-span-3"><x-ui.button type="submit" variant="primary" size="sm">{{ __('Create Session') }}</x-ui.button></div>
        </form>
    </div>
    @endcan

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Cycle') }}</th>
                    <th class="p-3 text-left">{{ __('Dates') }}</th>
                    <th class="p-3 text-left">{{ __('Appraisals') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($sessions as $session)
                <tr class="border-t">
                    <td class="p-3">{{ $session->name }}</td>
                    <td class="p-3">{{ $session->cycle?->name }}</td>
                    <td class="p-3">{{ $session->start_date->toDateString() }} – {{ $session->end_date->toDateString() }}</td>
                    <td class="p-3">{{ $session->employee_appraisals_count }}</td>
                    <td class="p-3">{{ $statuses[$session->status] ?? $session->status }}</td>
                    <td class="p-3"><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.appraisal-sessions.show', $session) }}">{{ __('View') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-slate-500">{{ __('No sessions found.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $sessions->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
