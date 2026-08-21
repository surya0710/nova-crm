<x-app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">{{ __('Working Days') }}</h1></x-slot>
    <x-flash-messages />
    <div class="mb-4">
        <x-nav.configuration-breadcrumbs :current="__('Working Days')" />
    </div>
    <p class="mb-4 text-sm text-slate-500">{{ __('HRMS consumes these organization-level working days for leave and attendance calculations.') }}</p>
    <form method="POST" action="{{ route('organization.settings.working-days.update') }}" class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf @method('PUT')
        <div class="grid grid-cols-2 gap-3">
            @foreach ($allDays as $day)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="working_days[]" value="{{ $day }}" @checked(in_array($day, $workingDays, true)) class="rounded border-slate-300">
                    {{ __(ucfirst($day)) }}
                </label>
            @endforeach
        </div>
        <x-primary-button>{{ __('Save Working Days') }}</x-primary-button>
    </form>
</x-app-layout>
