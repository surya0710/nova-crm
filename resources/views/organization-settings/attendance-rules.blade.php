<x-app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">{{ __('Attendance Rules') }}</h1></x-slot>
    <x-flash-messages />
    <form method="POST" action="{{ route('organization.settings.attendance-rules.update') }}" class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-sm text-slate-600 mb-1">{{ __('Default Grace Minutes') }}</label>
            <input type="number" name="default_grace_minutes" value="{{ old('default_grace_minutes', $rules['default_grace_minutes']) }}" class="w-full rounded-md border-slate-300 text-sm" min="0" max="240">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">{{ __('Late Threshold Minutes') }}</label>
            <input type="number" name="late_threshold_minutes" value="{{ old('late_threshold_minutes', $rules['late_threshold_minutes']) }}" class="w-full rounded-md border-slate-300 text-sm" min="0" max="240">
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">{{ __('Early Clock-in Window (minutes)') }}</label>
            <input type="number" name="allow_early_clock_in_minutes" value="{{ old('allow_early_clock_in_minutes', $rules['allow_early_clock_in_minutes']) }}" class="w-full rounded-md border-slate-300 text-sm" min="0" max="240">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="overtime_requires_approval" value="1" @checked(old('overtime_requires_approval', $rules['overtime_requires_approval'])) class="rounded border-slate-300">
            {{ __('Overtime requires approval') }}
        </label>
        <x-primary-button>{{ __('Save Attendance Rules') }}</x-primary-button>
    </form>
</x-app-layout>
