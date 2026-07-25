@php
    $platformUser = auth('platform')->user();
    $canManage = $platformUser->hasPermission('platform.organizations.manage');
    $canImpersonate = $platformUser->hasPermission('platform.impersonate');
    $canManageSubscriptions = $platformUser->hasPermission('platform.subscriptions.manage');
@endphp

<x-platform-layout>
    <x-layouts.entity-detail
        :title="$organization->name"
        :subtitle="__('Organization profile and lifecycle management')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Organizations'), 'href' => route('platform.organizations.index')],
                ['label' => $organization->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if ($canManage)
                <x-ui.button :href="route('platform.organizations.edit', $organization)" variant="secondary" size="sm">
                    {{ __('Edit') }}
                </x-ui.button>
            @endif
        </x-slot:actions>

        <x-entity.section :title="__('Profile')">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Email') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $organization->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Slug') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $organization->slug }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Phone') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $organization->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Website') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $organization->website ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Plan') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $organization->planLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Status') }}</dt>
                    <dd class="mt-1">
                        <x-ui.badge :variant="$organization->isActive() ? 'success' : ($organization->isSuspended() ? 'warning' : 'neutral')">
                            {{ $organization->status->label() }}
                        </x-ui.badge>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Owner') }}</dt>
                    <dd class="mt-1 text-sm text-ink">
                        {{ $owner?->name ?? '—' }}
                        @if ($owner?->email)
                            <span class="block text-xs text-ink-muted">{{ $owner->email }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Industry Template') }}</dt>
                    <dd class="mt-1 text-sm text-ink">
                        @if ($template_application)
                            {{ $template_application->template?->name ?? __('Deleted template') }}
                            · v{{ $template_application->version?->version ?? '—' }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Created') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $organization->created_at->format('M j, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Last Activity') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $organization->last_activity_at?->diffForHumans() ?? '—' }}</dd>
                </div>
            </dl>
        </x-entity.section>

        <x-entity.section :title="__('Subscription')">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Status') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ ucfirst($subscription['status'] ?? '—') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Plan') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ config('platform.plans.' . ($subscription['plan'] ?? $organization->plan), $subscription['plan'] ?? $organization->plan) }}</dd>
                </div>
                @if (! empty($subscription['trial_ends_at']))
                    <div>
                        <dt class="text-xs font-medium text-ink-muted">{{ __('Trial Ends') }}</dt>
                        <dd class="mt-1 text-sm text-ink">{{ \Illuminate\Support\Carbon::parse($subscription['trial_ends_at'])->format('M j, Y') }}</dd>
                    </div>
                @endif
                @if (! empty($subscription['renews_at']))
                    <div>
                        <dt class="text-xs font-medium text-ink-muted">{{ __('Renews At') }}</dt>
                        <dd class="mt-1 text-sm text-ink">{{ \Illuminate\Support\Carbon::parse($subscription['renews_at'])->format('M j, Y') }}</dd>
                    </div>
                @endif
            </dl>

            @if ($canManageSubscriptions)
                <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                    <form method="POST" action="{{ route('platform.subscriptions.start-trial', $organization) }}" class="inline-flex items-end gap-2">
                        @csrf
                        <x-forms.field :label="__('Trial days')" name="days" class="space-y-0">
                            <x-forms.input type="number" name="days" value="14" min="1" max="90" class="w-24" />
                        </x-forms.field>
                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Start Trial') }}</x-ui.button>
                    </form>
                    <form method="POST" action="{{ route('platform.subscriptions.end-trial', $organization) }}">
                        @csrf
                        <input type="hidden" name="convert" value="1">
                        <x-ui.button type="submit" variant="ghost" size="sm">{{ __('End Trial') }}</x-ui.button>
                    </form>
                </div>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Usage')">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ([
                    'users' => __('Users'),
                    'leads' => __('Leads'),
                    'customers' => __('Customers'),
                    'opportunities' => __('Opportunities'),
                    'invoices' => __('Invoices'),
                ] as $key => $label)
                    <x-ui.stat-card :label="$label" :value="number_format($usage[$key] ?? 0)" />
                @endforeach
            </div>
            <div class="mt-4">
                <x-ui.stat-card :label="__('Revenue Managed')" :value="number_format($counts['revenue_managed'], 2)" />
            </div>
        </x-entity.section>

        <x-entity.section :title="__('Storage')">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Used') }}</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink-heading">{{ number_format($usage['storage_mb'], 2) }} MB</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('Quota') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $modules['quotas']['storage_mb'] ?? __('Plan default') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-muted">{{ __('API Tokens') }}</dt>
                    <dd class="mt-1 text-sm text-ink">{{ number_format($api_tokens) }}</dd>
                </div>
            </dl>
        </x-entity.section>

        <x-entity.section :title="__('Modules')">
            <p class="mb-3 text-sm text-ink-muted">
                {{ __('Plan') }}: <span class="font-medium text-ink">{{ config('platform.plans.' . ($modules['plan'] ?? $organization->plan), $modules['plan'] ?? $organization->plan) }}</span>
            </p>
            @if (! empty($modules['enabled_modules']))
                <div class="flex flex-wrap gap-2">
                    @foreach ($modules['enabled_modules'] as $moduleKey)
                        <x-ui.badge variant="neutral">{{ $moduleKey }}</x-ui.badge>
                    @endforeach
                </div>
            @elseif (! empty($modules['available_modules']))
                <div class="flex flex-wrap gap-2">
                    @foreach ($modules['available_modules'] as $moduleKey)
                        <x-ui.badge variant="success">{{ $moduleKey }}</x-ui.badge>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-ink-muted">{{ __('No module data available.') }}</p>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Administrators')">
            @if ($administrators->isEmpty())
                <p class="text-sm text-ink-muted">{{ __('No administrators found.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-3 py-2 text-left font-medium">{{ __('Name') }}</th>
                                <th scope="col" class="px-3 py-2 text-left font-medium">{{ __('Email') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($administrators as $admin)
                                <tr>
                                    <td class="px-3 py-2 text-ink">{{ $admin->name }}</td>
                                    <td class="px-3 py-2 text-ink-muted">{{ $admin->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Activity')">
            <div class="divide-y divide-line">
                @forelse ($recent_logins as $login)
                    <div class="py-3">
                        <div class="text-sm font-medium text-ink-heading">{{ $login->name }}</div>
                        <div class="text-xs text-ink-muted">{{ $login->email }}</div>
                    </div>
                @empty
                    <p class="py-4 text-sm text-ink-muted">{{ __('No recent login activity.') }}</p>
                @endforelse
            </div>
        </x-entity.section>

        <x-entity.section :title="__('Audit Log')">
            <div class="divide-y divide-line">
                @forelse ($recent_audit as $log)
                    <div class="flex flex-col gap-1 py-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="text-sm font-medium text-ink-heading">{{ $log->event }}</div>
                            <div class="text-xs text-ink-muted">{{ $log->subject ?? '—' }} · {{ $log->user?->name ?? __('System') }}</div>
                        </div>
                        <span class="shrink-0 text-xs text-ink-muted">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <x-ui.empty-state-preset variant="platform_audit" />
                @endforelse
            </div>
            <div class="mt-4">
                <x-ui.button :href="route('platform.audit.index', ['organization_id' => $organization->id])" variant="ghost" size="sm">
                    {{ __('View full audit log') }}
                </x-ui.button>
            </div>
        </x-entity.section>

        <x-slot:aside>
            <x-entity.section :title="__('Actions')">
                <div class="space-y-2">
                    @if ($canManage)
                        @if ($organization->isActive())
                            <form method="POST" action="{{ route('platform.organizations.suspend', $organization) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" class="w-full">{{ __('Suspend') }}</x-ui.button>
                            </form>
                            <form method="POST" action="{{ route('platform.organizations.archive', $organization) }}" onsubmit="return confirm(@js(__('Archive this organization?')))">
                                @csrf
                                <x-ui.button type="submit" variant="ghost" class="w-full">{{ __('Archive') }}</x-ui.button>
                            </form>
                        @elseif ($organization->isSuspended())
                            <form method="POST" action="{{ route('platform.organizations.activate', $organization) }}">
                                @csrf
                                <x-ui.button type="submit" variant="primary" class="w-full">{{ __('Activate') }}</x-ui.button>
                            </form>
                            <form method="POST" action="{{ route('platform.organizations.restore', $organization) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" class="w-full">{{ __('Restore') }}</x-ui.button>
                            </form>
                        @elseif ($organization->isArchived())
                            <form method="POST" action="{{ route('platform.organizations.activate', $organization) }}">
                                @csrf
                                <x-ui.button type="submit" variant="primary" class="w-full">{{ __('Reactivate') }}</x-ui.button>
                            </form>
                            <form method="POST" action="{{ route('platform.organizations.restore', $organization) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary" class="w-full">{{ __('Restore') }}</x-ui.button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('platform.organizations.destroy', $organization) }}" onsubmit="return confirm(@js(__('Delete this organization? This archives the tenant.')))">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger" class="w-full">{{ __('Delete') }}</x-ui.button>
                        </form>
                    @endif

                    @if ($canImpersonate && $organization->isActive())
                        <form method="POST" action="{{ route('platform.organizations.impersonate', $organization) }}">
                            @csrf
                            <x-ui.button type="submit" variant="primary" class="w-full">{{ __('Impersonate') }}</x-ui.button>
                        </form>
                    @endif

                    @if ($canManage)
                        <x-ui.button :href="route('platform.organizations.edit', $organization)" variant="secondary" class="w-full">
                            {{ __('Edit Organization') }}
                        </x-ui.button>
                    @endif
                </div>
            </x-entity.section>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-platform-layout>
