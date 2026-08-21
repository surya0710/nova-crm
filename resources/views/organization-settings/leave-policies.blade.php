<x-app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">{{ __('Leave Policies') }}</h1></x-slot>
    <x-flash-messages />
    <div class="mb-4">
        <x-nav.configuration-breadcrumbs :current="__('Leave Policies')" />
    </div>
    <p class="mb-4 text-sm text-slate-500">{{ __('Organization defaults. Leave types may override individual rules.') }}</p>
    <form method="POST" action="{{ route('organization.settings.leave-policies.update') }}" class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf @method('PUT')
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="require_manager_approval" value="1" @checked(old('require_manager_approval', $policies['require_manager_approval'])) class="rounded border-slate-300"> {{ __('Require manager approval') }}</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="require_hr_approval" value="1" @checked(old('require_hr_approval', $policies['require_hr_approval'])) class="rounded border-slate-300"> {{ __('Require HR approval') }}</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_negative_balance" value="1" @checked(old('allow_negative_balance', $policies['allow_negative_balance'])) class="rounded border-slate-300"> {{ __('Allow negative leave balance') }}</label>
        <div>
            <label class="block text-sm text-slate-600 mb-1">{{ __('Cancellation cutoff (days)') }}</label>
            <input type="number" name="cancellation_cutoff_days" value="{{ old('cancellation_cutoff_days', $policies['cancellation_cutoff_days']) }}" class="w-full rounded-md border-slate-300 text-sm" min="0" max="90">
        </div>
        <x-primary-button>{{ __('Save Leave Policies') }}</x-primary-button>
    </form>
</x-app-layout>
