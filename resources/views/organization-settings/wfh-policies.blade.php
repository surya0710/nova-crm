<x-app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">{{ __('WFH Policies') }}</h1></x-slot>
    <x-flash-messages />
    <form method="POST" action="{{ route('organization.settings.wfh-policies.update') }}" class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf @method('PUT')
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $policies['enabled'])) class="rounded border-slate-300">
            {{ __('Enable work from home') }}
        </label>
        <div>
            <label class="block text-sm text-slate-600 mb-1">{{ __('Default policy type') }}</label>
            <select name="default_policy_type" class="w-full rounded-md border-slate-300 text-sm">
                @foreach ($policyTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('default_policy_type', $policies['default_policy_type']) === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">{{ __('Allowed weekdays') }}</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($weekdays as $value => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="allowed_weekdays[]" value="{{ $value }}"
                            @checked(in_array((int) $value, array_map('intval', old('allowed_weekdays', $policies['allowed_weekdays'])), true))
                            class="rounded border-slate-300">
                        {{ __($label) }}
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <label class="block text-sm text-slate-600 mb-1">{{ __('Cancellation cutoff (days before WFH date)') }}</label>
            <input type="number" name="cancellation_cutoff_days" value="{{ old('cancellation_cutoff_days', $policies['cancellation_cutoff_days']) }}" class="w-full rounded-md border-slate-300 text-sm" min="0" max="90">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="requires_approval" value="1" @checked(old('requires_approval', $policies['requires_approval'])) class="rounded border-slate-300">
            {{ __('Daily WFH requests require approval') }}
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="requires_hr_approval" value="1" @checked(old('requires_hr_approval', $policies['requires_hr_approval'])) class="rounded border-slate-300">
            {{ __('Also require HR approval step') }}
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="bypass_geofence" value="1" @checked(old('bypass_geofence', $policies['bypass_geofence'])) class="rounded border-slate-300">
            {{ __('Bypass office geofence while on WFH') }}
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="record_gps_when_wfh" value="1" @checked(old('record_gps_when_wfh', $policies['record_gps_when_wfh'])) class="rounded border-slate-300">
            {{ __('Still require GPS coordinates while on WFH') }}
        </label>
        <x-primary-button>{{ __('Save WFH Policies') }}</x-primary-button>
    </form>
</x-app-layout>
