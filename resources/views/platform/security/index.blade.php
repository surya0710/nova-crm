@php
    $canManage = auth('platform')->user()->hasPermission('platform.security.manage');
    $policies = $overview['policies'];
@endphp

<x-platform-layout>
    <x-layouts.settings
        :title="__('Security')"
        :subtitle="__('Platform-wide authentication and session policies')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Security'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat-card :label="__('Platform MFA Ready')" :value="number_format($overview['platform_users_mfa_ready'] ?? 0)" />
            <x-ui.stat-card :label="__('Locked Platform Users')" :value="number_format($overview['platform_users_locked'] ?? 0)" />
            <x-ui.stat-card :label="__('Active Sessions')" :value="number_format($overview['active_sessions'] ?? 0)" />
            <x-ui.stat-card :label="__('API Tokens')" :value="number_format($overview['api_tokens'] ?? 0)" />
        </div>

        <x-entity.section :title="__('Security Policies')">
            @if ($canManage)
                <form method="POST" action="{{ route('platform.security.update-policies') }}" class="space-y-6">
                    @csrf

                    <x-forms.section :title="__('Multi-Factor Authentication')">
                        <x-forms.field name="mfa_required_for_platform" class="sm:col-span-2">
                            <x-forms.checkbox name="mfa_required_for_platform" value="1" :label="__('Require MFA for platform users')" @checked(old('mfa_required_for_platform', $policies['mfa_required_for_platform'] ?? false)) />
                        </x-forms.field>
                        <x-forms.field name="mfa_required_for_tenants" class="sm:col-span-2">
                            <x-forms.checkbox name="mfa_required_for_tenants" value="1" :label="__('Require MFA for tenant users')" @checked(old('mfa_required_for_tenants', $policies['mfa_required_for_tenants'] ?? false)) />
                        </x-forms.field>
                    </x-forms.section>

                    <x-forms.section :title="__('Password Policy')">
                        <x-forms.field :label="__('Minimum Length')" name="password_min_length">
                            <x-forms.input type="number" name="password_min_length" value="{{ old('password_min_length', $policies['password_min_length'] ?? 8) }}" min="8" max="128" />
                        </x-forms.field>
                        <x-forms.field name="password_require_special">
                            <x-forms.checkbox name="password_require_special" value="1" :label="__('Require special characters')" @checked(old('password_require_special', $policies['password_require_special'] ?? true)) />
                        </x-forms.field>
                    </x-forms.section>

                    <x-forms.section :title="__('Session Policy')">
                        <x-forms.field :label="__('Session Lifetime (minutes)')" name="session_lifetime_minutes">
                            <x-forms.input type="number" name="session_lifetime_minutes" value="{{ old('session_lifetime_minutes', $policies['session_lifetime_minutes'] ?? 120) }}" min="5" max="10080" />
                        </x-forms.field>
                        <x-forms.field :label="__('Max Failed Logins')" name="max_failed_logins">
                            <x-forms.input type="number" name="max_failed_logins" value="{{ old('max_failed_logins', $policies['max_failed_logins'] ?? 5) }}" min="1" max="50" />
                        </x-forms.field>
                        <x-forms.field name="trusted_devices_enabled" class="sm:col-span-2">
                            <x-forms.checkbox name="trusted_devices_enabled" value="1" :label="__('Enable trusted devices')" @checked(old('trusted_devices_enabled', $policies['trusted_devices_enabled'] ?? true)) />
                        </x-forms.field>
                    </x-forms.section>

                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save Policies') }}</x-ui.button>
                </form>
            @else
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ([
                        'mfa_required_for_platform' => __('MFA required (platform)'),
                        'mfa_required_for_tenants' => __('MFA required (tenants)'),
                        'password_min_length' => __('Password min length'),
                        'session_lifetime_minutes' => __('Session lifetime (minutes)'),
                        'max_failed_logins' => __('Max failed logins'),
                    ] as $key => $label)
                        <div>
                            <dt class="text-xs text-ink-muted">{{ $label }}</dt>
                            <dd class="mt-1 text-sm text-ink">{{ is_bool($policies[$key] ?? null) ? (($policies[$key] ?? false) ? __('Yes') : __('No')) : ($policies[$key] ?? '—') }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Security Events')" class="mt-6">
            @if (empty($overview['recent_security_events']))
                <x-ui.empty-state-preset variant="platform_audit" :description="__('Security-related audit events will appear here.')" />
            @else
                <div class="divide-y divide-line">
                    @foreach ($overview['recent_security_events'] as $event)
                        <div class="flex flex-col gap-1 py-3 sm:flex-row sm:justify-between">
                            <div>
                                <div class="text-sm font-medium text-ink-heading">{{ $event['event'] }}</div>
                                <div class="text-xs text-ink-muted">{{ $event['subject'] ?? '—' }} · {{ $event['user'] ?? __('System') }} · {{ $event['organization'] ?? __('Platform') }}</div>
                            </div>
                            <span class="text-xs text-ink-muted">{{ $event['created_at']?->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-entity.section>
    </x-layouts.settings>
</x-platform-layout>
