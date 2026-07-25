@php
    $status = $overview['status'] ?? 'unknown';
    $badgeVariant = match ($status) {
        'strong' => 'success',
        'moderate' => 'warning',
        'needs_attention' => 'danger',
        default => 'neutral',
    };
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Security')"
        :subtitle="__('Password, session, and authentication policies')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Security'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-6 flex items-center gap-3">
            <span class="text-sm text-ink-muted">{{ __('Status') }}</span>
            <x-ui.badge :variant="$badgeVariant">{{ __(ucfirst(str_replace('_', ' ', $status))) }}</x-ui.badge>
            <span class="text-xs text-ink-muted">{{ __(':count security events in the last 30 days', ['count' => $overview['recent_security_events'] ?? 0]) }}</span>
        </div>

        <x-entity.section :title="__('Security policies')">
            <form method="POST" action="{{ route('administration.security.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-forms.section :title="__('Password policy')">
                    <x-forms.field :label="__('Minimum length')" name="password_min_length">
                        <x-forms.input type="number" name="password_min_length" min="6" max="128" value="{{ old('password_min_length', $policies['password_min_length']) }}" />
                    </x-forms.field>
                    <x-forms.field name="password_require_uppercase" class="sm:col-span-2">
                        <x-forms.checkbox name="password_require_uppercase" value="1" :label="__('Require uppercase')" @checked(old('password_require_uppercase', $policies['password_require_uppercase'])) />
                    </x-forms.field>
                    <x-forms.field name="password_require_number" class="sm:col-span-2">
                        <x-forms.checkbox name="password_require_number" value="1" :label="__('Require number')" @checked(old('password_require_number', $policies['password_require_number'])) />
                    </x-forms.field>
                    <x-forms.field name="password_require_special" class="sm:col-span-2">
                        <x-forms.checkbox name="password_require_special" value="1" :label="__('Require special character')" @checked(old('password_require_special', $policies['password_require_special'])) />
                    </x-forms.field>
                </x-forms.section>

                <x-forms.section :title="__('Authentication & sessions')">
                    <x-forms.field name="mfa_required" class="sm:col-span-2">
                        <x-forms.checkbox name="mfa_required" value="1" :label="__('Require multi-factor authentication')" @checked(old('mfa_required', $policies['mfa_required'])) />
                    </x-forms.field>
                    <x-forms.field :label="__('Session lifetime (minutes)')" name="session_lifetime_minutes">
                        <x-forms.input type="number" name="session_lifetime_minutes" min="5" max="10080" value="{{ old('session_lifetime_minutes', $policies['session_lifetime_minutes']) }}" />
                    </x-forms.field>
                    <x-forms.field :label="__('Max concurrent sessions')" name="max_concurrent_sessions">
                        <x-forms.input type="number" name="max_concurrent_sessions" min="1" max="50" value="{{ old('max_concurrent_sessions', $policies['max_concurrent_sessions']) }}" />
                    </x-forms.field>
                    <x-forms.field name="trusted_devices_enabled" class="sm:col-span-2">
                        <x-forms.checkbox name="trusted_devices_enabled" value="1" :label="__('Enable trusted devices')" @checked(old('trusted_devices_enabled', $policies['trusted_devices_enabled'])) />
                    </x-forms.field>
                    <x-forms.field :label="__('API token expiry (days)')" name="api_token_expiry_days">
                        <x-forms.input type="number" name="api_token_expiry_days" min="1" max="3650" value="{{ old('api_token_expiry_days', $policies['api_token_expiry_days']) }}" />
                    </x-forms.field>
                </x-forms.section>

                <x-ui.button type="submit" variant="primary">{{ __('Save policies') }}</x-ui.button>
            </form>
        </x-entity.section>

        <x-entity.section :title="__('Login history')" class="mt-6">
            @if ($loginHistory->isEmpty())
                <x-ui.empty-state-preset variant="security" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-ink-muted">
                                <th class="py-2 pr-4">{{ __('When') }}</th>
                                <th class="py-2 pr-4">{{ __('User') }}</th>
                                <th class="py-2 pr-4">{{ __('Event') }}</th>
                                <th class="py-2">{{ __('Subject') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($loginHistory as $log)
                                <tr>
                                    <td class="py-2.5 pr-4 text-ink-muted whitespace-nowrap">{{ $log->created_at?->diffForHumans() }}</td>
                                    <td class="py-2.5 pr-4 text-ink-heading">{{ $log->user?->name ?? __('System') }}</td>
                                    <td class="py-2.5 pr-4">{{ $log->event_label ?? $log->event }}</td>
                                    <td class="py-2.5 text-ink-muted">{{ $log->subject ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-entity.section>
    </x-layouts.settings>
</x-app-layout>
