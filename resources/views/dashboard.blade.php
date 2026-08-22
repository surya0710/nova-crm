@php
    $homeQuickActions = collect($quickActions ?? [])
        ->map(fn (array $action) => [
            'label' => $action['name'] ?? $action['label'] ?? '',
            'href' => $action['url'] ?? $action['href'] ?? null,
            'variant' => 'secondary',
        ])
        ->filter(fn (array $action) => filled($action['href']) && filled($action['label']))
        ->values()
        ->all();

    $notificationItems = collect($notifications['items'] ?? []);
    $unreadCount = (int) ($notifications['unread_count'] ?? 0);
    $assignedWork = collect($recentActivities['assigned_work'] ?? []);
    $recentActions = collect($recentActivities['recent_actions'] ?? []);
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('Home Workspace')"
        :subtitle="__(':org — your workspace overview', ['org' => $organization->name])"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Home Workspace'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if (! empty($homeQuickActions))
                <x-workspace.quick-actions :actions="$homeQuickActions" />
            @endif
            @if (Auth::user()->hasPermission('settings.manage', $organization))
                <x-ui.button :href="route('organization.settings.hub')" variant="secondary" size="sm">
                    {{ __('Settings') }}
                </x-ui.button>
            @endif
        </x-slot:actions>

        <x-slot:kpis>
            <x-ui.stat-card
                :label="__('Total :label', ['label' => crm_term('leads')])"
                :value="$leadStats['total']"
                :hint="__(':count open', ['count' => $leadStats['open']])"
            />
            <x-ui.stat-card
                :label="__('New :label', ['label' => crm_term('leads')])"
                :value="$leadStats['new']"
                :hint="__('Awaiting contact')"
            />
            <x-ui.stat-card
                :label="__('Won :label', ['label' => crm_term('leads')])"
                :value="$leadStats['won']"
                :hint="__('Closed successfully')"
            />
            <x-ui.stat-card
                :label="crm_term('customers')"
                :value="$customerStats['total']"
                :hint="__(':count active', ['count' => $customerStats['active']])"
            />
        </x-slot:kpis>

        <div class="space-y-6">
            @if (Auth::user()->hasPermission('tasks.view'))
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-ui.stat-card
                        :label="__('Open Tasks')"
                        :value="$taskStats['open']"
                        :hint="__('Pending or in progress')"
                    />
                    <x-ui.stat-card
                        :label="__('Due Today')"
                        :value="$taskStats['due_today']"
                        :hint="__('Needs attention today')"
                    />
                    <x-ui.stat-card
                        :label="__('Overdue')"
                        :value="$taskStats['overdue']"
                        :hint="__('Past due date')"
                    />
                </div>
            @endif

            @if (! empty($widgets))
                <x-workspace.widget
                    :title="__('Workspace Widgets')"
                    :subtitle="__('Personalized widgets based on your permissions and subscription')"
                >
                    <div class="mb-3 flex justify-end" id="workspace-widgets">
                        @if (Auth::user()->hasPermission('dashboard.customize', $organization))
                            <form method="POST" action="{{ route('dashboard.preferences.reset') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-ink-muted hover:text-primary-700">
                                    {{ __('Reset layout') }}
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($widgets as $widget)
                            <div class="rounded-lg border border-line bg-surface-muted/30 p-3" data-widget-key="{{ $widget['widget_key'] }}">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted">{{ $widget['module'] }}</p>
                                <p class="mt-1 text-sm font-semibold text-ink-heading">{{ $widget['name'] }}</p>
                                @if (! empty($widget['description']))
                                    <p class="mt-1 text-xs text-ink-muted">{{ $widget['description'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-workspace.widget>
            @endif

            @if (Auth::user()->hasPermission('leads.view'))
                <x-workspace.widget
                    :title="__('Recent Leads')"
                    :subtitle="__('Latest additions to your pipeline')"
                    :href="route('leads.index')"
                    :link-label="auth()->user()->can('create', App\Models\Lead::class) ? __('Add Lead') : __('View all')"
                >
                    @if ($recentLeads->isEmpty())
                        <x-ui.empty-state-preset
                            variant="leads"
                            :action-href="auth()->user()->can('create', App\Models\Lead::class) ? route('leads.create') : null"
                            class="!py-6"
                        />
                    @else
                        <ul class="divide-y divide-line -mx-1">
                            @foreach ($recentLeads as $lead)
                                <li class="py-2.5">
                                    <a href="{{ route('leads.show', $lead) }}" class="group flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-ink-heading group-hover:text-primary-700 truncate">{{ $lead->name }}</p>
                                            <p class="text-xs text-ink-muted truncate">{{ $lead->company ?? $lead->source_label }}</p>
                                        </div>
                                        <x-ui.badge variant="primary">{{ $lead->status_label }}</x-ui.badge>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-workspace.widget>
            @endif

            @if (Auth::user()->hasPermission('tasks.view'))
                <x-workspace.widget
                    :title="__('Upcoming Tasks')"
                    :subtitle="__('Follow-ups due soon')"
                    :href="route('tasks.index')"
                >
                    @if ($upcomingTasks->isEmpty())
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No open tasks.') }}</p>
                    @else
                        <ul class="divide-y divide-line -mx-1">
                            @foreach ($upcomingTasks as $task)
                                <li class="py-2.5">
                                    <a href="{{ route('tasks.show', $task) }}" class="block group">
                                        <p class="text-sm font-medium text-ink-heading group-hover:text-primary-700">{{ $task->title }}</p>
                                        <p class="text-xs text-ink-muted mt-0.5">
                                            {{ $task->status_label }}
                                            @if ($task->due_at)
                                                · {{ $task->due_at->format('M j, g:i A') }}
                                            @endif
                                            @if ($task->isOverdue())
                                                · <span class="text-danger">{{ __('Overdue') }}</span>
                                            @endif
                                        </p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-workspace.widget>
            @endif

            <x-workspace.widget :title="__('Recent Activity')">
                @if ($assignedWork->isEmpty() && $recentActions->isEmpty())
                    <x-ui.empty-state-preset variant="activities" class="!py-6" />
                @else
                    <ul class="divide-y divide-line -mx-1">
                        @foreach ($assignedWork as $task)
                            <li class="py-2.5 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink-heading truncate">{{ $task->title }}</p>
                                    <p class="text-xs text-ink-muted">{{ __('Assigned work') }} · {{ $task->status }}</p>
                                </div>
                                @if ($task->due_at)
                                    <span class="text-xs text-ink-muted shrink-0">{{ $task->due_at->diffForHumans() }}</span>
                                @endif
                            </li>
                        @endforeach
                        @foreach ($recentActions as $action)
                            <li class="py-2.5 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink-heading truncate">{{ $action->event }}</p>
                                    <p class="text-xs text-ink-muted truncate">{{ $action->subject }}</p>
                                </div>
                                <span class="text-xs text-ink-muted shrink-0">{{ $action->created_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-workspace.widget>

            <x-workspace.widget
                :title="__('Workspace shortcuts')"
                :subtitle="__('Jump into active modules')"
            >
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach ([
                        ['name' => crm_term('leads'), 'route' => 'leads.index', 'permission' => 'leads.view'],
                        ['name' => crm_term('customers'), 'route' => 'customers.index', 'permission' => 'customers.view'],
                        ['name' => crm_term('pipeline'), 'route' => 'pipeline.index', 'permission' => 'opportunities.view'],
                        ['name' => crm_term('products'), 'route' => 'products.index', 'permission' => 'products.view'],
                        ['name' => crm_term('quotations'), 'route' => 'quotations.index', 'permission' => 'quotations.view'],
                        ['name' => crm_term('invoices'), 'route' => 'invoices.index', 'permission' => 'invoices.view'],
                        ['name' => crm_term('payments'), 'route' => 'payments.index', 'permission' => 'payments.view'],
                        ['name' => __('Tasks'), 'route' => 'tasks.index', 'permission' => 'tasks.view'],
                        ['name' => __('Reports'), 'route' => 'reports.index', 'permission' => 'reports.view'],
                    ] as $module)
                        @if (Auth::user()->hasPermission($module['permission'] ?? '') && \Illuminate\Support\Facades\Route::has($module['route']))
                            <a href="{{ route($module['route']) }}" class="rounded-lg border border-line px-3 py-3 text-center text-sm font-medium text-ink-heading hover:border-primary-300 hover:bg-primary-50/40 hover:text-primary-700 transition">
                                {{ $module['name'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </x-workspace.widget>
        </div>

        <x-slot:aside>
            <x-workspace.attention-rail :title="__('Needs attention')">
                @if (Auth::user()->hasPermission('tasks.view') && ($taskStats['overdue'] ?? 0) > 0)
                    <x-workspace.attention-item
                        :href="route('tasks.index', ['filter' => 'overdue'])"
                        :title="__(':count overdue tasks', ['count' => $taskStats['overdue']])"
                        :subtitle="__('Past due date')"
                        :badge="__('Overdue')"
                        badge-variant="danger"
                    />
                @endif
                @if (Auth::user()->hasPermission('tasks.view') && ($taskStats['due_today'] ?? 0) > 0)
                    <x-workspace.attention-item
                        :href="route('tasks.index', ['filter' => 'due_today'])"
                        :title="__(':count tasks due today', ['count' => $taskStats['due_today']])"
                        :subtitle="__('Needs attention today')"
                        :badge="__('Today')"
                    />
                @endif
                @if (Auth::user()->hasPermission('leads.view') && ($leadStats['new'] ?? 0) > 0)
                    <x-workspace.attention-item
                        :href="route('leads.index')"
                        :title="__(':count new :label', ['count' => $leadStats['new'], 'label' => crm_term('leads')])"
                        :subtitle="__('Awaiting contact')"
                        :badge="__('New')"
                        badge-variant="primary"
                    />
                @endif
            </x-workspace.attention-rail>

            <x-workspace.widget :title="__('Notifications')">
                @if ($notificationItems->isEmpty())
                    <p class="text-sm text-ink-muted py-4 text-center">{{ __('No notifications.') }}</p>
                @else
                    @if ($unreadCount > 0)
                        <p class="mb-3 text-xs font-medium text-ink-muted">{{ __(':count unread', ['count' => $unreadCount]) }}</p>
                    @endif
                    <ul class="divide-y divide-line -mx-1">
                        @foreach ($notificationItems as $item)
                            <li class="py-2.5">
                                <p class="text-sm font-medium text-ink-heading truncate">{{ $item['title'] ?: __('Notification') }}</p>
                                @if (! empty($item['message']))
                                    <p class="text-xs text-ink-muted mt-0.5 line-clamp-2">{{ $item['message'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-workspace.widget>

            <x-entity.section :title="__('Organization')">
                <div class="flex items-center gap-3 pb-4 border-b border-line">
                    <x-organization-logo :organization="$organization" size="xl" />
                    <div class="min-w-0">
                        <p class="font-semibold text-ink-heading truncate">{{ $organization->name }}</p>
                        <p class="text-sm text-ink-muted truncate">{{ $organization->slug }}</p>
                    </div>
                </div>
                <dl class="mt-4 space-y-3">
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-ink-muted">{{ __('Currency') }}</dt>
                        <dd class="text-sm font-medium text-ink-heading">{{ $organization->currency }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-ink-muted">{{ __('Timezone') }}</dt>
                        <dd class="text-sm font-medium text-ink-heading text-right">{{ $organization->timezone }}</dd>
                    </div>
                    @if ($organization->email)
                        <div class="flex justify-between gap-4">
                            <dt class="text-sm text-ink-muted">{{ __('Email') }}</dt>
                            <dd class="text-sm font-medium text-ink-heading text-right truncate">{{ $organization->email }}</dd>
                        </div>
                    @endif
                </dl>
            </x-entity.section>
        </x-slot:aside>
    </x-layouts.workspace-home>
</x-app-layout>
