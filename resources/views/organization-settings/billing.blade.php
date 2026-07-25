<x-app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">{{ __('Billing') }}</h1></x-slot>
    <div class="max-w-xl rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
        <p>{{ __('Billing details for this organization are managed by your platform administrator.') }}</p>
        <p class="mt-2">{{ __('Organization ID') }}: <span class="font-mono text-slate-900">{{ $organization->id }}</span></p>
    </div>
</x-app-layout>
