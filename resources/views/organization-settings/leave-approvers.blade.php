<x-app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">{{ __('Leave Approvers') }}</h1></x-slot>
    <x-flash-messages />
    <form method="POST" action="{{ route('organization.settings.leave-approvers.update') }}" class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-sm text-slate-600 mb-1">{{ __('Primary approval chain') }}</label>
            <select name="primary_chain" class="w-full rounded-md border-slate-300 text-sm">
                <option value="reporting_manager" @selected(old('primary_chain', $approvers['primary_chain']) === 'reporting_manager')>{{ __('Reporting Manager') }}</option>
                <option value="department_head" @selected(old('primary_chain', $approvers['primary_chain']) === 'department_head')>{{ __('Department Head') }}</option>
                <option value="hr" @selected(old('primary_chain', $approvers['primary_chain']) === 'hr')>{{ __('HR') }}</option>
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="fallback_to_hr" value="1" @checked(old('fallback_to_hr', $approvers['fallback_to_hr'])) class="rounded border-slate-300">
            {{ __('Fallback to HR when manager is unavailable') }}
        </label>
        <div>
            <label class="block text-sm text-slate-600 mb-1">{{ __('Notes') }}</label>
            <textarea name="notes" rows="3" class="w-full rounded-md border-slate-300 text-sm">{{ old('notes', $approvers['notes']) }}</textarea>
        </div>
        <x-primary-button>{{ __('Save Leave Approvers') }}</x-primary-button>
    </form>
</x-app-layout>
