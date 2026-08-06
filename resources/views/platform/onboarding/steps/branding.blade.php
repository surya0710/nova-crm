<form id="onboarding-step-form" method="post" action="{{ route('platform.onboarding.steps', $onboarding) }}" class="mt-6 space-y-4">
    @csrf
    <input type="hidden" name="step" value="branding">

    <div class="grid gap-4 sm:grid-cols-2">
        <x-forms.field :label="__('Primary color')" name="primary_color">
            <x-forms.input name="primary_color" value="{{ old('primary_color', $stepData['primary_color'] ?? '#1d4ed8') }}" placeholder="#1d4ed8" />
        </x-forms.field>
        <x-forms.field :label="__('Accent color')" name="accent_color">
            <x-forms.input name="accent_color" value="{{ old('accent_color', $stepData['accent_color'] ?? '#0f172a') }}" placeholder="#0f172a" />
        </x-forms.field>
        <x-forms.field :label="__('Email from name')" name="email_from_name">
            <x-forms.input name="email_from_name" value="{{ old('email_from_name', $stepData['email_from_name'] ?? $onboarding->organization?->name) }}" />
        </x-forms.field>
        <x-forms.field :label="__('Email header text')" name="email_header_text">
            <x-forms.input name="email_header_text" value="{{ old('email_header_text', $stepData['email_header_text'] ?? $onboarding->organization?->name) }}" />
        </x-forms.field>
        <x-forms.field :label="__('Login headline')" name="login_headline" class="sm:col-span-2">
            <x-forms.input name="login_headline" value="{{ old('login_headline', $stepData['login_headline'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Login tagline')" name="login_tagline" class="sm:col-span-2">
            <x-forms.input name="login_tagline" value="{{ old('login_tagline', $stepData['login_tagline'] ?? '') }}" />
        </x-forms.field>
        <x-forms.field :label="__('Document / export footer')" name="document_footer" class="sm:col-span-2">
            <x-forms.input name="document_footer" value="{{ old('document_footer', $stepData['document_footer'] ?? '') }}" />
        </x-forms.field>
    </div>

    <p class="text-xs text-ink-muted">{{ __('Logo upload remains available in Administration → Branding after go-live.') }}</p>

    @include('platform.onboarding.partials.actions')
</form>
