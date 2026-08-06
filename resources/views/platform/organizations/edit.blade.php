<x-platform-layout>
    @php
        $tab = $tab ?? 'general';
        $editTabs = [
            ['key' => 'general', 'label' => __('Organization')],
            ['key' => 'subscription', 'label' => __('Subscription')],
            ['key' => 'modules', 'label' => __('Modules')],
            ['key' => 'limits', 'label' => __('Limits')],
            ['key' => 'billing', 'label' => __('Billing')],
            ['key' => 'activity', 'label' => __('Activity')],
        ];
        $tabItems = collect($editTabs)->map(fn ($item) => [
            'label' => $item['label'],
            'href' => route('platform.organizations.edit', ['organization' => $organization, 'tab' => $item['key']]),
            'active' => $tab === $item['key'],
        ])->all();
        $moduleCatalog = $licensing['module_catalog'] ?? [];
        $enabledModules = collect($moduleCatalog)->filter(fn ($m) => $m['enabled'] ?? false)->pluck('key')->all();
        $quotas = $licensing['quotas'] ?? [];
    @endphp

    <x-layouts.edit
        :title="__('Edit Organization')"
        :subtitle="$organization->name"
        max-width="5xl"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Organizations'), 'href' => route('platform.organizations.index')],
                ['label' => $organization->name, 'href' => route('platform.organizations.show', $organization)],
                ['label' => __('Edit'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.tabs :tabs="$tabItems" class="mb-6" />

        @if ($tab === 'general')
            <form method="POST" action="{{ route('platform.organizations.update', $organization) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <x-forms.section :title="__('Organization Details')">
                    <x-forms.field :label="__('Name')" name="name" required>
                        <x-forms.input name="name" value="{{ old('name', $organization->name) }}" required />
                    </x-forms.field>
                    <x-forms.field :label="__('Email')" name="email">
                        <x-forms.input type="email" name="email" value="{{ old('email', $organization->email) }}" />
                    </x-forms.field>
                    <x-forms.field :label="__('Phone')" name="phone">
                        <x-forms.input name="phone" value="{{ old('phone', $organization->phone) }}" />
                    </x-forms.field>
                    <x-forms.field :label="__('Website')" name="website">
                        <x-forms.input type="url" name="website" value="{{ old('website', $organization->website) }}" />
                    </x-forms.field>
                    <x-forms.field :label="__('Plan')" name="plan" required>
                        <x-forms.select name="plan" required>
                            @foreach ($plans as $value => $label)
                                <option value="{{ $value }}" @selected(old('plan', $organization->plan) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Timezone')" name="timezone">
                        <x-forms.select name="timezone">
                            <option value="">{{ __('Use system default') }}</option>
                            @foreach ($timezones as $timezone)
                                <option value="{{ $timezone }}" @selected(old('timezone', $organization->timezone) === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Currency')" name="currency">
                        <x-forms.select name="currency">
                            <option value="">{{ __('Use system default') }}</option>
                            @foreach ($currencies as $code => $label)
                                <option value="{{ $code }}" @selected(old('currency', $organization->currency) === $code)>{{ $code }} — {{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </x-forms.field>
                    <x-forms.field :label="__('Tax Name')" name="tax_name">
                        <x-forms.input name="tax_name" value="{{ old('tax_name', $organization->tax_name) }}" />
                    </x-forms.field>
                </x-forms.section>

                <x-forms.footer :cancel-href="route('platform.organizations.show', $organization)" :submit-label="__('Save Changes')" />
            </form>
        @endif

        @if ($tab === 'subscription')
            <x-forms.section :title="__('Subscription')" :subtitle="__('Plan and commercial status')">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-ink-muted">{{ __('Plan') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-ink">{{ $plans[$organization->plan] ?? $organization->plan }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-muted">{{ __('Status') }}</dt>
                        <dd class="mt-1 text-sm font-medium text-ink">{{ $subscription['status'] ?? $organization->status }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-muted">{{ __('Users') }}</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $usage['users'] ?? $organization->users()->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-ink-muted">{{ __('Storage (MB)') }}</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $usage['storage_mb'] ?? 0 }}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-sm text-ink-muted">
                    {{ __('Change the commercial plan from the General tab. Manage trials from the organization profile.') }}
                    <a href="{{ route('platform.organizations.show', $organization) }}" class="text-primary-700 hover:underline">{{ __('Open profile') }}</a>
                </p>
            </x-forms.section>
        @endif

        @if ($tab === 'modules')
            <form method="POST" action="{{ route('platform.organizations.modules.update', $organization) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-forms.section
                    :title="__('Modules')"
                    :subtitle="__('Enable or disable platform modules for this organization. Future fields (trial, add-on, expiry) are reserved.')"
                >
                    @unless ($canManageLicensing)
                        <x-ui.alert variant="warning" class="mb-4">
                            {{ __('You can view modules but need licensing permission to change them.') }}
                        </x-ui.alert>
                    @endunless

                    <div class="divide-y divide-line rounded-lg border border-line">
                        @forelse ($moduleCatalog as $module)
                            @php
                                $checked = in_array($module['key'], old('modules', $enabledModules), true);
                                $licensable = $module['licensable'] ?? true;
                            @endphp
                            <label class="flex items-start gap-4 p-4 {{ $licensable ? 'cursor-pointer hover:bg-surface-muted/40' : 'opacity-80' }}">
                                <div class="pt-1">
                                    @if ($licensable)
                                        <input
                                            type="checkbox"
                                            name="modules[]"
                                            value="{{ $module['key'] }}"
                                            class="rounded border-line text-primary-600 focus:ring-primary-500"
                                            @checked($checked)
                                            @disabled(! $canManageLicensing)
                                        >
                                    @else
                                        <input type="checkbox" checked disabled class="rounded border-line text-primary-600">
                                        <input type="hidden" name="modules[]" value="{{ $module['key'] }}">
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-ink-heading">{{ $module['name'] }}</span>
                                        @if ($module['plan_allows'] ?? false)
                                            <x-ui.badge variant="success" size="sm">{{ __('Included in subscription') }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="neutral" size="sm">{{ __('Not in plan') }}</x-ui.badge>
                                        @endif
                                        @if ($module['is_trial'] ?? false)
                                            <x-ui.badge variant="warning" size="sm">{{ __('Trial') }}</x-ui.badge>
                                        @endif
                                        @if ($module['is_addon'] ?? false)
                                            <x-ui.badge variant="primary" size="sm">{{ __('Add-on') }}</x-ui.badge>
                                        @endif
                                        @if (! empty($module['expires_at']))
                                            <x-ui.badge variant="neutral" size="sm">{{ __('Expires') }}: {{ $module['expires_at'] }}</x-ui.badge>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-ink-muted">{{ $module['description'] }}</p>
                                    <p class="mt-1 text-xs text-ink-muted">
                                        {{ ($module['enabled'] ?? false) ? __('Enabled') : __('Disabled') }}
                                        @if (! empty($module['workspace']))
                                            · {{ __('Workspace') }}: {{ $module['workspace'] }}
                                        @endif
                                    </p>
                                </div>
                            </label>
                        @empty
                            <div class="p-6">
                                <x-ui.empty-state-preset variant="modules" />
                            </div>
                        @endforelse
                    </div>
                </x-forms.section>

                @if ($canManageLicensing)
                    <x-forms.footer :cancel-href="route('platform.organizations.show', $organization)" :submit-label="__('Save Modules')" />
                @endif
            </form>
        @endif

        @if ($tab === 'limits')
            <form method="POST" action="{{ route('platform.organizations.limits.update', $organization) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-forms.section :title="__('Limits & Quotas')" :subtitle="__('Override plan defaults for this organization')">
                    @unless ($canManageLicensing)
                        <x-ui.alert variant="warning" class="mb-4">
                            {{ __('Licensing permission is required to change quotas.') }}
                        </x-ui.alert>
                    @endunless

                    <x-forms.field :label="__('Users')" name="users">
                        <x-forms.input type="number" name="users" min="0" value="{{ old('users', $quotas['users'] ?? '') }}" :disabled="! $canManageLicensing" />
                    </x-forms.field>
                    <x-forms.field :label="__('Storage (MB)')" name="storage_mb">
                        <x-forms.input type="number" name="storage_mb" min="0" value="{{ old('storage_mb', $quotas['storage_mb'] ?? '') }}" :disabled="! $canManageLicensing" />
                    </x-forms.field>
                    <x-forms.field :label="__('API requests / day')" name="api_requests_per_day">
                        <x-forms.input type="number" name="api_requests_per_day" min="0" value="{{ old('api_requests_per_day', $quotas['api_requests_per_day'] ?? '') }}" :disabled="! $canManageLicensing" />
                    </x-forms.field>
                </x-forms.section>

                @if ($canManageLicensing)
                    <x-forms.footer :cancel-href="route('platform.organizations.show', $organization)" :submit-label="__('Save Limits')" />
                @endif
            </form>
        @endif

        @if ($tab === 'billing')
            <x-forms.section :title="__('Billing')" :subtitle="__('Invoices and commercial history')">
                <p class="text-sm text-ink-muted">
                    {{ __('Billing documents are managed in the platform subscriptions area.') }}
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('platform.subscriptions.index') }}" class="inline-flex items-center rounded-lg border border-line bg-surface px-3 py-2 text-sm font-medium text-ink hover:bg-surface-muted">
                        {{ __('Subscriptions') }}
                    </a>
                    <a href="{{ route('platform.invoices.index') }}" class="inline-flex items-center rounded-lg border border-line bg-surface px-3 py-2 text-sm font-medium text-ink hover:bg-surface-muted">
                        {{ __('Invoices') }}
                    </a>
                </div>
            </x-forms.section>
        @endif

        @if ($tab === 'activity')
            <x-forms.section :title="__('Recent Activity')">
                @if (($recent_audit ?? collect())->isEmpty())
                    <p class="text-sm text-ink-muted">{{ __('No recent audit events for this organization.') }}</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($recent_audit as $log)
                            <li class="py-3">
                                <p class="text-sm font-medium text-ink">{{ $log->action ?? $log->event ?? __('Event') }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">
                                    {{ $log->user?->name ?? __('System') }}
                                    · {{ optional($log->created_at)->diffForHumans() }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-forms.section>
        @endif
    </x-layouts.edit>
</x-platform-layout>
