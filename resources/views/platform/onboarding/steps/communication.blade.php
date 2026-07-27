<form id="onboarding-step-form" method="post" action="{{ route('platform.onboarding.steps', $onboarding) }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="step" value="communication">

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="inline-flex items-center gap-2 text-sm sm:col-span-2">
            <input type="hidden" name="mail_enabled" value="0">
            <input type="checkbox" name="mail_enabled" value="1" class="rounded border-line text-primary-600" @checked(old('mail_enabled', $stepData['mail_enabled'] ?? true))>
            {{ __('Enable organization email') }}
        </label>
        <x-forms.field :label="__('Driver')" name="mail_driver">
            <x-forms.select name="mail_driver">
                <option value="log" @selected(old('mail_driver', $stepData['mail_driver'] ?? 'log') === 'log')">log (dev)</option>
                <option value="smtp" @selected(old('mail_driver', $stepData['mail_driver'] ?? '') === 'smtp')">smtp</option>
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('From address')" name="mail_from_address">
            <x-forms.input name="mail_from_address" value="{{ old('mail_from_address', $stepData['mail_from_address'] ?? $onboarding->organization?->email) }}" />
        </x-forms.field>
        <x-forms.field :label="__('From name')" name="mail_from_name">
            <x-forms.input name="mail_from_name" value="{{ old('mail_from_name', $stepData['mail_from_name'] ?? $onboarding->organization?->name) }}" />
        </x-forms.field>
        <x-forms.field :label="__('SMTP host')" name="mail_host">
            <x-forms.input name="mail_host" value="{{ old('mail_host', $stepData['mail_host'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('SMTP port')" name="mail_port">
            <x-forms.input name="mail_port" value="{{ old('mail_port', $stepData['mail_port'] ?? '587') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Username')" name="mail_username">
            <x-forms.input name="mail_username" value="{{ old('mail_username', $stepData['mail_username'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Password')" name="mail_password">
            <x-forms.input type="password" name="mail_password" value="" autocomplete="new-password" />
        </x-forms.field>
    </div>

    <label class="inline-flex items-center gap-2 text-sm">
        <input type="hidden" name="verify" value="0">
        <input type="checkbox" name="verify" value="1" class="rounded border-line text-primary-600" @checked(old('verify', true))>
        {{ __('Verify configuration before continuing') }}
    </label>

    @include('platform.onboarding.partials.actions')
</form>
