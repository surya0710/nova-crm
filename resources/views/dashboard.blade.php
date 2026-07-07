<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Dashboard') }}</h1>
            <p class="text-sm text-slate-500 hidden sm:block">{{ $organization->name }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    {{-- Welcome --}}
    <div class="rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 p-6 sm:p-8 text-white shadow-lg shadow-indigo-500/20">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            <x-organization-logo :organization="$organization" size="xl" class="ring-2 ring-white/20" />
            <div class="flex-1 min-w-0">
                <p class="text-indigo-200 text-sm">{{ __('Welcome back,') }} <span class="text-white font-medium">{{ Auth::user()->name }}</span></p>
                <h2 class="mt-1 text-2xl sm:text-3xl font-bold truncate">{{ $organization->name }}</h2>
                <p class="mt-2 text-sm text-indigo-100/90 leading-relaxed">
                    {{ __('Your workspace is ready. Track leads, manage customers, and grow your sales pipeline.') }}
                </p>
            </div>
            @if (Auth::user()->hasPermission('settings.manage', $organization))
                <a href="{{ route('organization.edit') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white text-indigo-700 px-5 py-2.5 text-sm font-semibold hover:bg-indigo-50 transition shrink-0 shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ __('Settings') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach ([
            ['label' => __('Total :label', ['label' => crm_term('leads')]), 'value' => $leadStats['total'], 'sub' => __(':count open', ['count' => $leadStats['open']]), 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'link' => Auth::user()->hasPermission('leads.view') ? route('leads.index') : null],
            ['label' => __('New :label', ['label' => crm_term('leads')]), 'value' => $leadStats['new'], 'sub' => __('Awaiting contact'), 'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'link' => null],
            ['label' => __('Won :label', ['label' => crm_term('leads')]), 'value' => $leadStats['won'], 'sub' => __('Closed successfully'), 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'link' => null],
            ['label' => crm_term('customers'), 'value' => $customerStats['total'], 'sub' => __(':count active', ['count' => $customerStats['active']]), 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'link' => Auth::user()->hasPermission('customers.view') ? route('customers.index') : null],
        ] as $stat)
            <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stat['value'] }}</p>
                    </div>
                    <div class="h-10 w-10 rounded-lg {{ $stat['bg'] }} {{ $stat['text'] }} flex items-center justify-center shrink-0">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $stat['icon'] }}"/></svg>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-400">
                    @if ($stat['link'])
                        <a href="{{ $stat['link'] }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ $stat['sub'] }} →</a>
                    @else
                        {{ $stat['sub'] }}
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    @if (Auth::user()->hasPermission('tasks.view'))
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach ([
                ['label' => __('Open Tasks'), 'value' => $taskStats['open'], 'sub' => __('Pending or in progress'), 'link' => route('tasks.index'), 'bg' => 'bg-violet-50', 'text' => 'text-violet-600'],
                ['label' => __('Due Today'), 'value' => $taskStats['due_today'], 'sub' => __('Needs attention today'), 'link' => route('tasks.index', ['filter' => 'due_today']), 'bg' => 'bg-amber-50', 'text' => 'text-amber-600'],
                ['label' => __('Overdue'), 'value' => $taskStats['overdue'], 'sub' => __('Past due date'), 'link' => route('tasks.index', ['filter' => 'overdue']), 'bg' => 'bg-red-50', 'text' => 'text-red-600'],
            ] as $stat)
                <a href="{{ $stat['link'] }}" class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm hover:shadow-md hover:border-indigo-200 transition-shadow block">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $stat['value'] }}</p>
                    <p class="mt-3 text-xs text-indigo-600 font-medium">{{ $stat['sub'] }} →</p>
                </a>
            @endforeach
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Recent leads --}}
        @if (Auth::user()->hasPermission('leads.view'))
            <div class="xl:col-span-2 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ __('Recent Leads') }}</h3>
                        <p class="text-sm text-slate-500 mt-0.5">{{ __('Latest additions to your pipeline') }}</p>
                    </div>
                    @can('create', App\Models\Lead::class)
                        <a href="{{ route('leads.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Add Lead') }}</a>
                    @endcan
                </div>
                @if ($recentLeads->isEmpty())
                    <div class="p-8 text-center text-sm text-slate-500">
                        {{ __('No leads yet.') }}
                        @can('create', App\Models\Lead::class)
                            <a href="{{ route('leads.create') }}" class="block mt-2 text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Create your first lead') }} →</a>
                        @endcan
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($recentLeads as $lead)
                            <a href="{{ route('leads.show', $lead) }}" class="flex items-center justify-between gap-4 px-6 py-4 hover:bg-slate-50 transition">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $lead->name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ $lead->company ?? $lead->source_label }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-medium text-slate-600">{{ $lead->status_label }}</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/30">
                        <a href="{{ route('leads.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('View all leads') }} →</a>
                    </div>
                @endif
            </div>
        @else
            <div class="xl:col-span-2 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Quick Actions') }}</h3>
                    <p class="text-sm text-slate-500 mt-0.5">{{ __('Common tasks to get started') }}</p>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if (Auth::user()->hasPermission('settings.manage', $organization))
                        <a href="{{ route('organization.edit') }}" class="group flex items-start gap-4 rounded-xl border border-slate-200 p-5 hover:border-indigo-300 hover:bg-indigo-50/40 transition">
                            <div class="h-11 w-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900 group-hover:text-indigo-700">{{ __('Organization Settings') }}</p>
                                <p class="text-sm text-slate-500 mt-1">{{ __('Logo, address, tax info & preferences') }}</p>
                            </div>
                        </a>
                    @endif
                    <a href="{{ route('profile.edit') }}" class="group flex items-start gap-4 rounded-xl border border-slate-200 p-5 hover:border-indigo-300 hover:bg-indigo-50/40 transition">
                        <div class="h-11 w-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 group-hover:text-indigo-700">{{ __('My Profile') }}</p>
                            <p class="text-sm text-slate-500 mt-1">{{ __('Account, password & security') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        @endif

        {{-- Sidebar column --}}
        <div class="space-y-6">
            @if (Auth::user()->hasPermission('tasks.view'))
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ __('Upcoming Tasks') }}</h3>
                            <p class="text-sm text-slate-500 mt-0.5">{{ __('Follow-ups due soon') }}</p>
                        </div>
                        @can('create', App\Models\Task::class)
                            <a href="{{ route('tasks.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Add') }}</a>
                        @endcan
                    </div>
                    @if ($upcomingTasks->isEmpty())
                        <div class="p-8 text-center text-sm text-slate-500">{{ __('No open tasks.') }}</div>
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach ($upcomingTasks as $task)
                                <a href="{{ route('tasks.show', $task) }}" class="block px-6 py-4 hover:bg-slate-50/80 transition">
                                    <p class="text-sm font-medium text-slate-900">{{ $task->title }}</p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ $task->status_label }}
                                        @if ($task->due_at)
                                            · {{ $task->due_at->format('M j, g:i A') }}
                                        @endif
                                        @if ($task->isOverdue())
                                            · <span class="text-red-600">{{ __('Overdue') }}</span>
                                        @endif
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Org overview --}}
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Organization') }}</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center gap-4 pb-5 border-b border-slate-100">
                    <x-organization-logo :organization="$organization" size="lg" />
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 truncate">{{ $organization->name }}</p>
                        <p class="text-sm text-slate-500 truncate">{{ $organization->slug }}</p>
                    </div>
                </div>
                <dl class="mt-5 space-y-4">
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-slate-500">{{ __('Currency') }}</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $organization->currency }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-slate-500">{{ __('Timezone') }}</dt>
                        <dd class="text-sm font-medium text-slate-900 text-right">{{ $organization->timezone }}</dd>
                    </div>
                    @if ($organization->email)
                        <div class="flex justify-between gap-4">
                            <dt class="text-sm text-slate-500">{{ __('Email') }}</dt>
                            <dd class="text-sm font-medium text-slate-900 text-right truncate">{{ $organization->email }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
        </div>
    </div>

    {{-- Modules --}}
    <div class="mt-6 rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-semibold text-slate-900">{{ __('CRM Modules') }}</h3>
            <p class="text-sm text-slate-500 mt-0.5">{{ __('Your sales toolkit') }}</p>
        </div>
        <div class="p-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach ([
                ['name' => crm_term('leads'), 'active' => true, 'route' => route('leads.index'), 'permission' => 'leads.view'],
                ['name' => crm_term('customers'), 'active' => true, 'route' => route('customers.index'), 'permission' => 'customers.view'],
                ['name' => crm_term('pipeline'), 'active' => true, 'route' => route('pipeline.index'), 'permission' => 'opportunities.view'],
                ['name' => crm_term('products'), 'active' => true, 'route' => route('products.index'), 'permission' => 'products.view'],
                ['name' => crm_term('quotations'), 'active' => true, 'route' => route('quotations.index'), 'permission' => 'quotations.view'],
                ['name' => crm_term('invoices'), 'active' => true, 'route' => route('invoices.index'), 'permission' => 'invoices.view'],
                ['name' => crm_term('payments'), 'active' => true, 'route' => route('payments.index'), 'permission' => 'payments.view'],
                ['name' => __('Tasks'), 'active' => true, 'route' => route('tasks.index'), 'permission' => 'tasks.view'],
                ['name' => __('Reports'), 'active' => true, 'route' => route('reports.index'), 'permission' => 'reports.view'],
            ] as $module)
                @if ($module['active'] && Auth::user()->hasPermission($module['permission'] ?? ''))
                    <a href="{{ $module['route'] }}" class="rounded-xl border border-indigo-200 bg-indigo-50/50 px-3 py-5 text-center hover:border-indigo-300 hover:bg-indigo-50 transition">
                        <p class="text-sm font-medium text-indigo-700">{{ $module['name'] }}</p>
                        <span class="mt-2 inline-block text-[10px] font-semibold uppercase tracking-wide text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">{{ __('Active') }}</span>
                    </a>
                @else
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-5 text-center">
                        <p class="text-sm font-medium text-slate-700">{{ $module['name'] }}</p>
                        <span class="mt-2 inline-block text-[10px] font-semibold uppercase tracking-wide text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded-full">{{ __('Soon') }}</span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-app-layout>
