<x-app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">{{ __('Subscription') }}</h1></x-slot>
    <div class="mb-4">
        <x-nav.configuration-breadcrumbs :current="__('Subscription')" />
    </div>
    <div class="max-w-xl rounded-xl border border-slate-200 bg-white p-6">
        <p class="text-sm text-slate-600">{{ __('Current plan') }}</p>
        <p class="mt-1 text-lg font-semibold text-slate-900">{{ $organization->planLabel() }}</p>
        <p class="mt-4 text-sm text-slate-500">{{ __('Status') }}: {{ $organization->status?->value ?? ($organization->is_active ? 'active' : 'inactive') }}</p>
    </div>
</x-app-layout>
