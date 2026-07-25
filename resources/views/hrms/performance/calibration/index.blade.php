<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Calibration Sessions')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Performance'), 'href' => route('hrms.performance.index')],
                ['label' => __('Calibration Sessions'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium mb-3">{{ __('Create Calibration') }}</h2>
        <form method="POST" action="{{ route('hrms.performance.calibration.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @csrf
            <x-forms.input name="name" placeholder="{{ __('Session Name') }}" required  />
            <select name="appraisal_session_id" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" required>
                <option value="">{{ __('Appraisal Session') }}</option>
                @foreach ($sessions as $session)
                    <option value="{{ $session->id }}">{{ $session->name }}</option>
                @endforeach
            </select>
            <x-forms.input name="scheduled_at" type="datetime-local"  />
            <div class="md:col-span-3"><x-ui.button type="submit" variant="primary" size="sm">{{ __('Create') }}</x-ui.button></div>
        </form>
    </div>

    <div class="rounded-xl bg-white border overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Session') }}</th>
                    <th class="p-3 text-left">{{ __('Status') }}</th>
                    <th class="p-3 text-left"></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($calibrations as $calibration)
                <tr class="border-t">
                    <td class="p-3">{{ $calibration->name }}</td>
                    <td class="p-3">{{ $calibration->session?->name }}</td>
                    <td class="p-3">{{ $statuses[$calibration->status] ?? $calibration->status }}</td>
                    <td class="p-3"><a class="text-sm font-medium text-primary-700 hover:text-primary-800" href="{{ route('hrms.performance.calibration.show', $calibration) }}">{{ __('View') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center text-slate-500">{{ __('No calibration sessions.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $calibrations->links() }}</div>
    </div>
    </x-layouts.entity-listing>
</x-app-layout>
