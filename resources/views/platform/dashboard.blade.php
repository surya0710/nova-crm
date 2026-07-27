<x-platform-layout>
    <x-layouts.workspace-home
        :title="__('Platform Home')"
        :subtitle="__('Operate NovaCRM as a commercial SaaS platform')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-workspace.quick-actions :actions="$quickActions" />
        </x-slot:actions>

        <x-slot:kpis>
            @foreach ($kpis as $kpi)
                <x-ui.stat-card
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :hint="$kpi['hint'] ?? null"
                />
            @endforeach
        </x-slot:kpis>

        <div class="space-y-6">
            @if (! empty($onboarding))
                <x-workspace.widget :title="__('Organization Onboarding')" :href="route('platform.onboarding.index')">
                    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4 text-sm">
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Pending setup') }}</dt>
                            <dd class="mt-1 text-xl font-semibold text-ink-heading">{{ $onboarding['pending_setup'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('In progress') }}</dt>
                            <dd class="mt-1 text-xl font-semibold text-ink-heading">{{ $onboarding['in_progress'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Ready') }}</dt>
                            <dd class="mt-1 text-xl font-semibold text-ink-heading">{{ $onboarding['ready'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Failed') }}</dt>
                            <dd class="mt-1 text-xl font-semibold text-ink-heading">{{ $onboarding['failed'] }}</dd>
                        </div>
                    </dl>
                    @if (($onboarding['active_sessions'] ?? collect())->isNotEmpty())
                        <ul class="mt-4 space-y-1 text-sm">
                            @foreach ($onboarding['active_sessions']->take(4) as $session)
                                <li>
                                    <a href="{{ route('platform.onboarding.show', $session) }}" class="text-primary-700 hover:underline">
                                        {{ $session->organization?->name ?? __('Draft #:id', ['id' => $session->id]) }}
                                    </a>
                                    <span class="text-ink-muted"> · {{ $session->progress_percent }}% · {{ $session->current_step }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-workspace.widget>
            @endif

            @if (in_array('org_totals', $widgets, true) || in_array('org_active', $widgets, true) || in_array('org_trial', $widgets, true) || in_array('org_expired', $widgets, true))
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @if (in_array('org_totals', $widgets, true))
                        <x-ui.stat-card :label="__('Total Organizations')" :value="number_format($metrics['organizations']['total'])" />
                    @endif
                    @if (in_array('org_active', $widgets, true))
                        <x-ui.stat-card :label="__('Active Organizations')" :value="number_format($metrics['organizations']['active'])" />
                    @endif
                    @if (in_array('org_trial', $widgets, true))
                        <x-ui.stat-card :label="__('Trial Organizations')" :value="number_format($metrics['organizations']['trial'])" />
                    @endif
                    @if (in_array('org_expired', $widgets, true))
                        <x-ui.stat-card :label="__('Expired Organizations')" :value="number_format($metrics['organizations']['expired'])" />
                    @endif
                </div>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                @if (in_array('users_active', $widgets, true))
                    <x-workspace.widget :title="__('Active Users')" :href="route('platform.global-users.index')">
                        <p class="text-3xl font-semibold text-ink-heading">{{ number_format($metrics['users']['active_today']) }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('Active today across all tenants') }}</p>
                    </x-workspace.widget>
                @endif

                @if (in_array('users_mau', $widgets, true))
                    <x-workspace.widget :title="__('Monthly Active Users')">
                        <p class="text-3xl font-semibold text-ink-heading">{{ number_format($metrics['users']['mau']) }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('Distinct users in the last 30 days') }}</p>
                    </x-workspace.widget>
                @endif

                @if (in_array('revenue_summary', $widgets, true))
                    <x-workspace.widget :title="__('Revenue Summary')" :href="route('platform.transactions.index')">
                        <p class="text-3xl font-semibold text-ink-heading">${{ number_format($metrics['revenue']['month'], 2) }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('Month-to-date succeeded transactions') }}</p>
                    </x-workspace.widget>
                @endif

                @if (in_array('subscription_overview', $widgets, true))
                    <x-workspace.widget :title="__('Subscription Overview')" :href="route('platform.subscriptions.index')">
                        <dl class="grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Active') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $metrics['subscriptions']['active'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Trial') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $metrics['subscriptions']['trial'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Expired') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $metrics['subscriptions']['expired'] }}</dd>
                            </div>
                        </dl>
                        <ul class="mt-3 space-y-1 text-xs text-ink-muted">
                            @foreach ($metrics['subscriptions']['by_plan'] as $plan => $count)
                                <li>{{ config("platform.plans.$plan", $plan) }}: {{ $count }}</li>
                            @endforeach
                        </ul>
                    </x-workspace.widget>
                @endif

                @if (in_array('storage_usage', $widgets, true))
                    <x-workspace.widget :title="__('Storage Usage')">
                        <p class="text-3xl font-semibold text-ink-heading">{{ number_format($metrics['storage']['bytes'] / 1048576, 1) }} MB</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('Across all organizations') }}</p>
                    </x-workspace.widget>
                @endif

                @if (in_array('queue_health', $widgets, true))
                    <x-workspace.widget :title="__('Queue Health')" :href="route('platform.monitoring.index')">
                        <x-ui.badge :variant="$metrics['queue']['health'] === 'healthy' ? 'success' : 'warning'">
                            {{ ucfirst($metrics['queue']['health']) }}
                        </x-ui.badge>
                        <p class="mt-2 text-sm text-ink-muted">
                            {{ __(':pending pending · :failed failed', ['pending' => $metrics['queue']['pending'], 'failed' => $metrics['queue']['failed']]) }}
                        </p>
                    </x-workspace.widget>
                @endif

                @if (in_array('background_jobs', $widgets, true))
                    <x-workspace.widget :title="__('Background Jobs')" :href="route('platform.monitoring.index')">
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Pending') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $metrics['queue']['pending'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Failed') }}</dt>
                                <dd class="mt-1 font-semibold text-ink-heading">{{ $metrics['queue']['failed'] }}</dd>
                            </div>
                        </dl>
                    </x-workspace.widget>
                @endif

                @if (in_array('api_requests', $widgets, true))
                    <x-workspace.widget :title="__('API Requests')">
                        <p class="text-sm text-ink-muted">{{ $metrics['api_requests']['label'] }}</p>
                    </x-workspace.widget>
                @endif

                @if (in_array('email_delivery', $widgets, true))
                    <x-workspace.widget :title="__('Email Delivery')">
                        <x-ui.badge :variant="$metrics['email_delivery']['status'] === 'configured' ? 'success' : 'warning'">
                            {{ ucfirst($metrics['email_delivery']['status']) }}
                        </x-ui.badge>
                        <p class="mt-2 text-sm text-ink-muted">{{ __('Mailer: :mailer', ['mailer' => $metrics['email_delivery']['mailer'] ?? '—']) }}</p>
                    </x-workspace.widget>
                @endif

                @if (in_array('provider_health', $widgets, true))
                    <x-workspace.widget :title="__('Provider Health')" :href="route('platform.providers.index')">
                        <p class="text-3xl font-semibold text-ink-heading">{{ $metrics['providers']['healthy'] }}/{{ $metrics['providers']['total'] }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('Configured providers') }}</p>
                    </x-workspace.widget>
                @endif
            </div>

            @if (in_array('recent_activity', $widgets, true))
                <x-workspace.widget :title="__('Recent Activity')" :href="route('platform.audit.index')">
                    <div class="divide-y divide-line">
                        @forelse ($metrics['recent_activity'] as $activity)
                            <div class="flex justify-between gap-4 py-3 text-sm">
                                <div>
                                    <div class="font-medium text-ink-heading">{{ $activity['organization'] ?? __('Platform') }}</div>
                                    <div class="text-ink-muted">{{ $activity['event'] }} · {{ $activity['user'] ?? __('System') }}</div>
                                </div>
                                <span class="shrink-0 text-xs text-ink-muted">{{ $activity['created_at']?->diffForHumans() }}</span>
                            </div>
                        @empty
                            <x-ui.empty-state-preset variant="platform_audit" />
                        @endforelse
                    </div>
                </x-workspace.widget>
            @endif
        </div>

        <x-slot:aside>
            @if (in_array('platform_alerts', $widgets, true))
                <x-workspace.attention-rail :title="__('Platform Alerts')">
                    @forelse ($alerts as $item)
                        <x-workspace.attention-item
                            :href="$item['href'] ?? null"
                            :title="$item['label']"
                            :badge-variant="$item['tone'] === 'danger' ? 'danger' : ($item['tone'] === 'info' ? 'info' : 'warning')"
                            :badge="$item['tone'] ? __(ucfirst($item['tone'])) : null"
                        />
                    @empty
                        <li class="px-4 py-6 text-sm text-ink-muted">{{ __('You are all caught up.') }}</li>
                    @endforelse
                </x-workspace.attention-rail>
            @endif

            @if (in_array('quick_actions', $widgets, true))
                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-sm font-semibold text-ink-heading">{{ __('Widget configuration') }}</h2>
                    </x-slot:header>
                    <form method="POST" action="{{ route('platform.dashboard.widgets') }}" class="space-y-2">
                        @csrf
                        @foreach ($availableWidgets as $widgetKey)
                            <label class="flex items-center gap-2 text-sm text-ink">
                                <input type="checkbox" name="widgets[]" value="{{ $widgetKey }}" @checked(in_array($widgetKey, $widgets, true)) class="rounded border-line text-primary-600 focus:ring-primary-500">
                                <span>{{ str_replace('_', ' ', ucfirst($widgetKey)) }}</span>
                            </label>
                        @endforeach
                        <x-ui.button type="submit" variant="primary" size="sm" class="mt-3">{{ __('Save layout') }}</x-ui.button>
                    </form>
                </x-ui.card>
            @endif
        </x-slot:aside>
    </x-layouts.workspace-home>
</x-platform-layout>
