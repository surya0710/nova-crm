@php
    $canManage = auth('platform')->user()->hasPermission('platform.configuration.manage');
    $sections = [
        'branding' => __('Branding'),
        'domains' => __('Domains'),
        'email_templates' => __('Email Templates'),
        'organization_defaults' => __('Organization Defaults'),
        'ai' => __('AI'),
        'regional' => __('Regional'),
    ];
@endphp

<x-platform-layout>
    <x-layouts.settings
        :title="__('Configuration')"
        :subtitle="__('Platform branding, domains, defaults, and integrations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Configuration'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:nav>
            <x-ui.card :padding="true">
                <nav class="space-y-1 text-sm" aria-label="{{ __('Configuration sections') }}">
                    @foreach ($sections as $key => $label)
                        <a href="#config-{{ $key }}" class="block rounded-md px-3 py-2 text-ink-muted hover:bg-surface-muted hover:text-ink-heading">
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>
            </x-ui.card>
        </x-slot:nav>

        @foreach ($sections as $groupKey => $groupLabel)
            <x-entity.section :title="$groupLabel" :id="'config-' . $groupKey">
                @if ($canManage)
                    <form method="POST" action="{{ route('platform.configuration.update') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="group" value="{{ $groupKey }}">

                        @if ($groupKey === 'branding')
                            <x-forms.section>
                                <x-forms.field :label="__('Product Name')" name="data[product_name]">
                                    <x-forms.input name="data[product_name]" value="{{ old('data.product_name', $configuration['branding']['product_name'] ?? '') }}" />
                                </x-forms.field>
                                <x-forms.field :label="__('Primary Color')" name="data[primary_color]">
                                    <x-forms.input name="data[primary_color]" value="{{ old('data.primary_color', $configuration['branding']['primary_color'] ?? '') }}" />
                                </x-forms.field>
                                <x-forms.field :label="__('Accent Color')" name="data[accent_color]">
                                    <x-forms.input name="data[accent_color]" value="{{ old('data.accent_color', $configuration['branding']['accent_color'] ?? '') }}" />
                                </x-forms.field>
                            </x-forms.section>
                        @elseif ($groupKey === 'domains')
                            <x-forms.section>
                                <x-forms.field :label="__('App URL')" name="data[app_url]">
                                    <x-forms.input type="url" name="data[app_url]" value="{{ old('data.app_url', $configuration['domains']['app_url'] ?? '') }}" />
                                </x-forms.field>
                                <x-forms.field :label="__('Platform URL')" name="data[platform_url]">
                                    <x-forms.input type="url" name="data[platform_url]" value="{{ old('data.platform_url', $configuration['domains']['platform_url'] ?? '') }}" />
                                </x-forms.field>
                            </x-forms.section>
                        @elseif ($groupKey === 'regional')
                            <x-forms.section>
                                <x-forms.field :label="__('Default Timezone')" name="data[default_timezone]">
                                    <x-forms.input name="data[default_timezone]" value="{{ old('data.default_timezone', $configuration['regional']['default_timezone'] ?? 'UTC') }}" />
                                </x-forms.field>
                                <x-forms.field :label="__('Default Currency')" name="data[default_currency]">
                                    <x-forms.input name="data[default_currency]" value="{{ old('data.default_currency', $configuration['regional']['default_currency'] ?? 'USD') }}" />
                                </x-forms.field>
                                <x-forms.field :label="__('Default Locale')" name="data[default_locale]">
                                    <x-forms.input name="data[default_locale]" value="{{ old('data.default_locale', $configuration['regional']['default_locale'] ?? 'en') }}" />
                                </x-forms.field>
                            </x-forms.section>
                        @elseif ($groupKey === 'ai')
                            <x-forms.section>
                                <x-forms.field name="data[enabled]">
                                    <x-forms.checkbox name="data[enabled]" value="1" :label="__('Enable AI features')" @checked(old('data.enabled', $configuration['ai']['enabled'] ?? false)) />
                                </x-forms.field>
                                <x-forms.field :label="__('Default Provider')" name="data[default_provider]">
                                    <x-forms.input name="data[default_provider]" value="{{ old('data.default_provider', $configuration['ai']['default_provider'] ?? '') }}" />
                                </x-forms.field>
                            </x-forms.section>
                        @elseif ($groupKey === 'organization_defaults')
                            <x-forms.section>
                                <x-forms.field :label="__('Default Plan')" name="data[plan]">
                                    <x-forms.select name="data[plan]">
                                        @foreach (config('platform.plans') as $value => $label)
                                            <option value="{{ $value }}" @selected(old('data.plan', $configuration['organization_defaults']['plan'] ?? 'starter') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </x-forms.select>
                                </x-forms.field>
                                <x-forms.field :label="__('Default Status')" name="data[status]">
                                    <x-forms.select name="data[status]">
                                        @foreach (config('platform.organization_statuses') as $value => $label)
                                            <option value="{{ $value }}" @selected(old('data.status', $configuration['organization_defaults']['status'] ?? 'active') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </x-forms.select>
                                </x-forms.field>
                            </x-forms.section>
                        @elseif ($groupKey === 'email_templates')
                            @foreach (['welcome', 'trial_ending'] as $templateKey)
                                <x-forms.section :title="ucfirst(str_replace('_', ' ', $templateKey))">
                                    <x-forms.field :label="__('Subject')" :name="'data['.$templateKey.'][subject]'">
                                        <x-forms.input name="data[{{ $templateKey }}][subject]" value="{{ old('data.'.$templateKey.'.subject', $configuration['email_templates'][$templateKey]['subject'] ?? '') }}" />
                                    </x-forms.field>
                                    <x-forms.field :label="__('Body')" :name="'data['.$templateKey.'][body]'" class="sm:col-span-2">
                                        <x-forms.textarea name="data[{{ $templateKey }}][body]" rows="4">{{ old('data.'.$templateKey.'.body', $configuration['email_templates'][$templateKey]['body'] ?? '') }}</x-forms.textarea>
                                    </x-forms.field>
                                </x-forms.section>
                            @endforeach
                        @endif

                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save :section', ['section' => $groupLabel]) }}</x-ui.button>
                    </form>
                @else
                    <pre class="overflow-auto rounded-lg bg-surface-muted/40 p-4 text-xs text-ink-muted">{{ json_encode($configuration[$groupKey] ?? [], JSON_PRETTY_PRINT) }}</pre>
                @endif
            </x-entity.section>
        @endforeach
    </x-layouts.settings>
</x-platform-layout>
