<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Branding')"
        :subtitle="__('Logo, colors, and customer-facing copy')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Branding'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="POST" action="{{ route('administration.branding.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <x-entity.section :title="__('Logo')">
                <x-logo-upload :current-url="$branding['logo_url'] ?? null" :removable="true" />
            </x-entity.section>

            <x-entity.section :title="__('Colors')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-forms.field :label="__('Primary color')" name="primary_color">
                        <x-forms.input name="primary_color" value="{{ old('primary_color', $branding['primary_color']) }}" placeholder="#0F766E" />
                    </x-forms.field>
                    <x-forms.field :label="__('Accent color')" name="accent_color">
                        <x-forms.input name="accent_color" value="{{ old('accent_color', $branding['accent_color']) }}" placeholder="#0EA5E9" />
                    </x-forms.field>
                </div>
            </x-entity.section>

            <x-entity.section :title="__('Email & login')">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-forms.field :label="__('Email from name')" name="email_from_name">
                        <x-forms.input name="email_from_name" value="{{ old('email_from_name', $branding['email_from_name']) }}" />
                    </x-forms.field>
                    <x-forms.field :label="__('Email header text')" name="email_header_text">
                        <x-forms.input name="email_header_text" value="{{ old('email_header_text', $branding['email_header_text']) }}" />
                    </x-forms.field>
                    <x-forms.field :label="__('Login headline')" name="login_headline" class="sm:col-span-2">
                        <x-forms.input name="login_headline" value="{{ old('login_headline', $branding['login_headline']) }}" />
                    </x-forms.field>
                    <x-forms.field :label="__('Login tagline')" name="login_tagline" class="sm:col-span-2">
                        <x-forms.input name="login_tagline" value="{{ old('login_tagline', $branding['login_tagline']) }}" />
                    </x-forms.field>
                    <x-forms.field :label="__('Document footer')" name="document_footer" class="sm:col-span-2">
                        <x-forms.textarea name="document_footer" rows="3">{{ old('document_footer', $branding['document_footer']) }}</x-forms.textarea>
                    </x-forms.field>
                </div>
            </x-entity.section>

            <x-ui.button type="submit" variant="primary">{{ __('Save branding') }}</x-ui.button>
        </form>
    </x-layouts.settings>
</x-app-layout>
