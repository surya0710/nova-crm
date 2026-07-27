<form id="onboarding-step-form" method="post" action="{{ route('platform.onboarding.steps', $onboarding) }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="step" value="users">

    <x-forms.section :title="__('Organization administrator')">
        <x-forms.field :label="__('Name')" name="administrator[name]" required>
            <x-forms.input name="administrator[name]" value="{{ old('administrator.name', $stepData['administrator']['name'] ?? '') }}" required />
        </x-forms.field>
        <x-forms.field :label="__('Email')" name="administrator[email]" required>
            <x-forms.input type="email" name="administrator[email]" value="{{ old('administrator.email', $stepData['administrator']['email'] ?? '') }}" required />
        </x-forms.field>
        <x-forms.field :label="__('Role')" name="administrator[role]">
            <x-forms.select name="administrator[role]">
                <option value="organization-owner" @selected(old('administrator.role', $stepData['administrator']['role'] ?? 'organization-owner') === 'organization-owner')">{{ __('Organization Owner') }}</option>
                <option value="organization-administrator" @selected(old('administrator.role', '') === 'organization-administrator')">{{ __('Organization Administrator') }}</option>
            </x-forms.select>
        </x-forms.field>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="hidden" name="administrator[send_invitation]" value="0">
            <input type="checkbox" name="administrator[send_invitation]" value="1" class="rounded border-line text-primary-600" @checked(old('administrator.send_invitation', $stepData['administrator']['send_invitation'] ?? true))>
            {{ __('Invite immediately (Identity Platform)') }}
        </label>
    </x-forms.section>

    <label class="inline-flex items-center gap-2 text-sm">
        <input type="hidden" name="import_employees" value="0">
        <input type="checkbox" name="import_employees" value="1" class="rounded border-line text-primary-600" @checked(old('import_employees', $stepData['import_employees'] ?? false))>
        {{ __('Plan to import employees next (Import Center)') }}
    </label>

    @include('platform.onboarding.partials.actions')
</form>
