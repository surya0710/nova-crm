<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Profile') }}</h1>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 sm:p-8">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 sm:p-8">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6 sm:p-8">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
