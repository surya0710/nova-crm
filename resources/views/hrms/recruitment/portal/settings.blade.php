<x-app-layout>
    <x-flash-messages />

    <x-layouts.edit
        :title="__('Candidate Portal Settings')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Recruitment'), 'href' => route('hrms.recruitment.dashboard')],
                ['label' => __('Candidate Portal Settings'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="rounded-xl border border-line bg-surface-card shadow-sm p-6">
        <form method="POST" action="{{ route('hrms.recruitment.portal.settings.update') }}" class="space-y-4">
            @csrf @method('PUT')
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="portal_enabled" value="1" @checked(old('portal_enabled', $settings->portal_enabled))> {{ __('Enable candidate portal') }}</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_guest_apply" value="1" @checked(old('allow_guest_apply', $settings->allow_guest_apply))> {{ __('Allow guest applications') }}</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="require_login_to_apply" value="1" @checked(old('require_login_to_apply', $settings->require_login_to_apply))> {{ __('Require login to apply') }}</label>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">{{ __('Save settings') }}</button>
        </form>
    </div>
    </x-layouts.edit>
</x-app-layout>
