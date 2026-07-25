<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Saved Recruitment Reports')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Saved Recruitment Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @can('create', \App\Models\RecruitmentSavedReport::class)
    <div class="rounded-xl border border-line bg-surface-card shadow-sm p-4 mb-6">
        <h2 class="font-medium text-slate-900 mb-3">{{ __('Save Report Configuration') }}</h2>
        <form method="POST" action="{{ route('hrms.recruitment.saved-reports.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Name') }}</label>
                <input type="text" name="report_name" required class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" value="{{ old('report_name') }}">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Type') }}</label>
                <select name="report_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @foreach ($reportTypes as $type => $label)
                        <option value="{{ $type }}">{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">{{ __('Period') }}</label>
                <select name="period" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}">{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="is_shared" value="1" class="rounded border-slate-300">
                    {{ __('Share') }}
                </label>
                <button type="submit" class="rounded-lg bg-slate-900 text-white text-sm px-4 py-2">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
    @endcan

    <div class="rounded-xl border border-line bg-surface-card shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3 text-left">{{ __('Name') }}</th>
                    <th class="p-3 text-left">{{ __('Type') }}</th>
                    <th class="p-3 text-left">{{ __('Owner') }}</th>
                    <th class="p-3 text-left">{{ __('Shared') }}</th>
                    <th class="p-3 text-left">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($reports as $report)
                <tr class="border-t">
                    <td class="p-3">
                        <a class="underline text-slate-900" href="{{ route('hrms.recruitment.saved-reports.show', $report) }}">{{ $report->report_name }}</a>
                    </td>
                    <td class="p-3">{{ $report->reportTypeLabel() }}</td>
                    <td class="p-3">{{ $report->user?->name }}</td>
                    <td class="p-3">{{ $report->is_shared ? __('Yes') : __('No') }}</td>
                    <td class="p-3 space-x-2">
                        @can('share', $report)
                        <form method="POST" action="{{ route('hrms.recruitment.saved-reports.share', $report) }}" class="inline">
                            @csrf
                            <button class="text-slate-600 hover:text-slate-900 underline">{{ $report->is_shared ? __('Unshare') : __('Share') }}</button>
                        </form>
                        @endcan
                        @can('delete', $report)
                        <form method="POST" action="{{ route('hrms.recruitment.saved-reports.destroy', $report) }}" class="inline" onsubmit="return confirm('{{ __('Delete this saved report?') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600 hover:text-red-800 underline">{{ __('Delete') }}</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr class="border-t"><td class="p-3 text-slate-500" colspan="5">{{ __('No saved reports yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $reports->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
