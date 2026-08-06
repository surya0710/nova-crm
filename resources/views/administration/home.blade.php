<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('Administration Workspace')"
        :subtitle="__('People, access, and organization configuration')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration Workspace'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-workspace.quick-actions :actions="$quickActions" />
        </x-slot:actions>

        <x-slot:kpis>
            @forelse ($kpis as $kpi)
                <x-ui.stat-card
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :hint="$kpi['hint'] ?? null"
                />
            @empty
                <x-ui.stat-card :label="__('Administration')" :value="__('—')" :hint="__('No metrics available for your role')" />
            @endforelse
        </x-slot:kpis>

        <div class="space-y-6">
            <x-workspace.widget
                :title="__('Organization summary')"
                :subtitle="__('Tenant profile')"
                :href="\Illuminate\Support\Facades\Route::has('organization.edit') && auth()->user()->hasPermission('settings.manage') ? route('organization.edit') : null"
            >
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Name') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ $summary['name'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Plan') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ $summary['plan'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Status') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ ucfirst((string) $summary['status']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Timezone / Currency') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">{{ ($summary['timezone'] ?? '—').' · '.($summary['currency'] ?? '—') }}</dd>
                    </div>
                </dl>
            </x-workspace.widget>

            <div class="grid gap-6 md:grid-cols-2">
                <x-workspace.widget
                    :title="__('Modules')"
                    :subtitle="__('Plan entitlements')"
                    :href="$modules['href'] ?? null"
                >
                    <p class="text-sm text-ink-heading">
                        <span class="font-semibold">{{ $modules['count'] }}</span>
                        <span class="text-ink-muted">{{ __('enabled on :plan', ['plan' => ucfirst($modules['plan'])]) }}</span>
                    </p>
                    @if (! empty($modules['enabled']))
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach (array_slice($modules['enabled'], 0, 8) as $module)
                                <li><x-ui.badge variant="primary">{{ __(ucfirst(str_replace('_', ' ', $module))) }}</x-ui.badge></li>
                            @endforeach
                        </ul>
                    @else
                        <x-ui.empty-state-preset variant="modules" class="!py-4" />
                    @endif
                </x-workspace.widget>

                <x-workspace.widget
                    :title="__('License')"
                    :subtitle="__('Subscription')"
                    :href="$license['href'] ?? null"
                >
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Plan') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $license['plan'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Status') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ ucfirst($license['status']) }}</dd>
                        </div>
                        @if ($license['seats'] !== null)
                            <div class="col-span-2">
                                <dt class="text-xs text-ink-muted">{{ __('Seats') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $license['seats'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-workspace.widget>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <x-workspace.widget
                    :title="__('Storage')"
                    :subtitle="__('Usage')"
                    :href="$storage['href'] ?? null"
                >
                    <p class="text-2xl font-semibold text-ink-heading">{{ $storage['used_label'] }}</p>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('Used across organization files') }}</p>
                </x-workspace.widget>

                @if ($apiUsage)
                    <x-workspace.widget
                        :title="__('API usage')"
                        :subtitle="__('Access tokens')"
                        :href="$apiUsage['href'] ?? null"
                    >
                        <p class="text-2xl font-semibold text-ink-heading">{{ $apiUsage['tokens'] }}</p>
                        <p class="mt-1 text-xs text-ink-muted">{{ __('Active Sanctum tokens') }}</p>
                    </x-workspace.widget>
                @endif
            </div>

            @if ($structure)
                <x-workspace.widget :title="__('Structure')" :subtitle="__('Org chart building blocks')">
                    <dl class="grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Departments') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $structure['departments'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Branches') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $structure['branches'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Designations') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $structure['designations'] }}</dd>
                        </div>
                    </dl>
                </x-workspace.widget>
            @endif

            @if ($integrations)
                <x-workspace.widget
                    :title="__('Integrations')"
                    :subtitle="__('Provider health')"
                    :href="$integrations['href'] ?? null"
                >
                    <div class="flex gap-6 text-sm mb-3">
                        <div>
                            <p class="text-xs text-ink-muted">{{ __('Connected') }}</p>
                            <p class="font-semibold text-ink-heading">{{ $integrations['connected'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">{{ __('Needs attention') }}</p>
                            <p class="font-semibold text-ink-heading">{{ $integrations['degraded'] }}</p>
                        </div>
                    </div>
                    @forelse ($integrations['cards'] as $card)
                        <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="font-medium text-ink-heading truncate">{{ $card['name'] ?? $card['slug'] }}</span>
                            <x-ui.badge :variant="($card['connected'] ?? false) ? 'success' : 'neutral'">
                                {{ $card['status_label'] ?? ($card['status'] ?? '—') }}
                            </x-ui.badge>
                        </div>
                    @empty
                        <x-ui.empty-state-preset variant="integrations" class="!py-4" />
                    @endforelse
                </x-workspace.widget>
            @endif

            <x-workspace.widget
                :title="__('Security status')"
                :subtitle="__('Policy posture')"
                :href="\Illuminate\Support\Facades\Route::has('administration.security.index') && auth()->user()->hasPermission('settings.manage') ? route('administration.security.index') : null"
            >
                @php
                    $status = $securityStatus['status'] ?? 'unknown';
                    $badgeVariant = match ($status) {
                        'strong' => 'success',
                        'moderate' => 'warning',
                        'needs_attention' => 'danger',
                        default => 'neutral',
                    };
                @endphp
                <div class="flex items-center gap-3">
                    <x-ui.badge :variant="$badgeVariant">{{ __(ucfirst(str_replace('_', ' ', $status))) }}</x-ui.badge>
                    <span class="text-sm text-ink-muted">
                        {{ __('MFA :state · :events events (30d)', [
                            'state' => ($securityStatus['mfa_required'] ?? false) ? __('required') : __('optional'),
                            'events' => $securityStatus['recent_security_events'] ?? 0,
                        ]) }}
                    </span>
                </div>
            </x-workspace.widget>

            <x-workspace.widget
                :title="__('Pending invitations')"
                :subtitle="__('Unverified members')"
                :href="auth()->user()->hasPermission('users.view') && \Illuminate\Support\Facades\Route::has('team.index') ? route('team.index') : null"
            >
                @forelse ($pendingInvitations as $invite)
                    <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="font-medium text-ink-heading truncate">{{ $invite->name }}</p>
                            <p class="text-xs text-ink-muted truncate">{{ $invite->email }}</p>
                        </div>
                        <x-ui.badge variant="warning">{{ __('Pending') }}</x-ui.badge>
                    </div>
                @empty
                    <x-ui.empty-state-preset variant="users" :description="__('No pending invitations.')" class="!py-4" />
                @endforelse
            </x-workspace.widget>

            <x-workspace.widget
                :title="__('Recent activity')"
                :href="auth()->user()->hasPermission('audit.view') && \Illuminate\Support\Facades\Route::has('audit-logs.index') ? route('audit-logs.index') : null"
            >
                @forelse ($recentActivity as $item)
                    <a href="{{ $item['href'] ?? '#' }}" class="flex items-start justify-between gap-3 py-2.5 border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink-heading truncate hover:text-primary-700">{{ $item['title'] }}</p>
                            <p class="text-xs text-ink-muted">{{ $item['subtitle'] }}</p>
                        </div>
                        <span class="text-xs text-ink-muted shrink-0">{{ $item['when'] }}</span>
                    </a>
                @empty
                    <x-ui.empty-state-preset variant="admin_audit" class="!py-6" />
                @endforelse
            </x-workspace.widget>
        </div>

        <x-slot:aside>
            <x-workspace.attention-rail :title="__('Needs attention')">
                @forelse ($attention as $item)
                    <x-workspace.attention-item
                        :href="$item['href'] ?? null"
                        :title="$item['title']"
                        :subtitle="$item['subtitle'] ?? null"
                        :badge="$item['badge'] ?? null"
                    />
                @empty
                    {{-- empty slot handled by rail --}}
                @endforelse
            </x-workspace.attention-rail>

            <x-entity.section :title="__('Quick links')">
                @if (\Illuminate\Support\Facades\Route::has('organization.settings.hub') && auth()->user()->hasPermission('settings.manage'))
                    <a href="{{ route('organization.settings.hub') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('Configuration Hub') }}</a>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('administration.developer.index') && auth()->user()->hasAnyPermission(['settings.manage', 'api.tokens']))
                    <a href="{{ route('administration.developer.index') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('Developer') }}</a>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('rbac.roles.index') && auth()->user()->hasPermission('rbac.view'))
                    <a href="{{ route('rbac.roles.index') }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ __('Roles') }}</a>
                @endif
            </x-entity.section>
        </x-slot:aside>
    </x-layouts.workspace-home>
</x-app-layout>
