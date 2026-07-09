@php
    $currentOrganization = app(\App\Services\TenantContext::class)->get();
    $user = Auth::user();
    $userOrganizations = $user->organizations;
    $currentRole = $currentOrganization ? $user->getRoleNameInOrganization($currentOrganization) : null;

    $can = fn (string $permission) => $user->hasPermission($permission, $currentOrganization);

    $iconHome = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>';
    $iconBuilding = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>';
    $iconUser = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
    $iconUsers = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
    $iconChart = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>';
    $iconBox = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
    $iconDoc = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
    $iconReceipt = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>';
    $iconPayment = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $iconShield = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
    $iconTask = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
    $iconFields = '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h10M4 18h7M17 10v8m-4-4h8"/></svg>';
@endphp

<aside class="h-full bg-slate-900 text-white flex flex-col w-64 overflow-hidden">
    <div class="shrink-0 flex items-center gap-3 px-5 py-5 border-b border-slate-800">
        <div class="h-9 w-9 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-sm">N</div>
        <div>
            <p class="font-semibold text-white leading-tight">NovaCRM</p>
            <p class="text-xs text-slate-400">Business Suite</p>
        </div>
    </div>

    @if ($currentOrganization)
        <div class="shrink-0 px-4 py-4 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <x-organization-logo :organization="$currentOrganization" size="md" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium truncate">{{ $currentOrganization->name }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ $currentOrganization->currency }} · {{ $currentOrganization->timezone }}</p>
                </div>
            </div>

            @if ($userOrganizations->count() > 1)
                <form id="org-switch-form" method="POST" action="#" class="mt-3">
                    @csrf
                    <select
                        class="w-full text-xs bg-slate-800 border-slate-700 text-slate-300 rounded-lg py-1.5 px-2 focus:ring-indigo-500 focus:border-indigo-500"
                        onchange="if (this.value) { this.form.action = '{{ url('organization/switch') }}/' + this.value; this.form.submit(); }"
                    >
                        @foreach ($userOrganizations as $org)
                            <option value="{{ $org->id }}" @selected($org->id === $currentOrganization->id)>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    @endif

    <nav class="flex-1 min-h-0 overflow-y-auto px-3 py-4 space-y-6 sidebar-scroll">
        @if ($currentOrganization)
            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Main</p>
                <div class="space-y-1">
                    <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" :icon="$iconHome">
                        {{ __('Dashboard') }}
                    </x-sidebar-link>
                </div>
            </div>

            @if ($can('leads.view') || $can('customers.view') || $can('opportunities.view') || $can('products.view') || $can('quotations.view') || $can('invoices.view') || $can('payments.view') || $can('tasks.view') || $can('reports.view'))
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">CRM</p>
                    <div class="space-y-1">
                        @if ($can('leads.view'))
                            <x-sidebar-link :href="route('leads.index')" :active="request()->routeIs('leads.*')" :icon="$iconUsers">{{ crm_term('leads') }}</x-sidebar-link>
                        @endif
                        @if ($can('customers.view'))
                            <x-sidebar-link :href="route('customers.index')" :active="request()->routeIs('customers.*')" :icon="$iconBuilding">{{ crm_term('customers') }}</x-sidebar-link>
                        @endif
                        @if ($can('opportunities.view'))
                            <x-sidebar-link :href="route('pipeline.index')" :active="request()->routeIs('pipeline.*')" :icon="$iconChart">{{ crm_term('pipeline') }}</x-sidebar-link>
                        @endif
                        @if ($can('products.view'))
                            <x-sidebar-link :href="route('products.index')" :active="request()->routeIs('products.*')" :icon="$iconBox">{{ crm_term('products') }}</x-sidebar-link>
                        @endif
                        @if ($can('quotations.view'))
                            <x-sidebar-link :href="route('quotations.index')" :active="request()->routeIs('quotations.*')" :icon="$iconDoc">{{ crm_term('quotations') }}</x-sidebar-link>
                        @endif
                        @if ($can('invoices.view'))
                            <x-sidebar-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')" :icon="$iconReceipt">{{ crm_term('invoices') }}</x-sidebar-link>
                        @endif
                        @if ($can('payments.view'))
                            <x-sidebar-link :href="route('payments.index')" :active="request()->routeIs('payments.*')" :icon="$iconPayment">{{ crm_term('payments') }}</x-sidebar-link>
                        @endif
                        @if ($can('tasks.view'))
                            <x-sidebar-link :href="route('tasks.index')" :active="request()->routeIs('tasks.*')" :icon="$iconTask">{{ __('Tasks') }}</x-sidebar-link>
                        @endif
                    </div>
                </div>
            @endif

            @if ($can('reports.view') || $can('finance.view'))
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Analytics') }}</p>
                    <div class="space-y-1">
                        @if ($can('reports.view'))
                            <x-sidebar-link :href="route('reports.index')" :active="request()->routeIs('reports.index')" :icon="$iconChart">{{ __('Reports') }}</x-sidebar-link>
                        @endif
                        @if ($can('reports.view') || $can('finance.view'))
                            <x-sidebar-link :href="route('reports.finance')" :active="request()->routeIs('reports.finance')" :icon="$iconChart">{{ __('Finance') }}</x-sidebar-link>
                        @endif
                        @if ($can('audit.view'))
                            <x-sidebar-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')" :icon="$iconShield">{{ __('Audit Log') }}</x-sidebar-link>
                        @endif
                    </div>
                </div>
            @elseif ($can('audit.view'))
                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Analytics') }}</p>
                    <div class="space-y-1">
                        <x-sidebar-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')" :icon="$iconShield">{{ __('Audit Log') }}</x-sidebar-link>
                    </div>
                </div>
            @endif

            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Settings</p>
                <div class="space-y-1">
                    @if ($can('settings.manage'))
                        <x-sidebar-link :href="route('organization.edit')" :active="request()->routeIs('organization.edit')" :icon="$iconBuilding">
                            {{ __('Organization') }}
                        </x-sidebar-link>
                    @endif
                    @if ($can('metadata.view') || $can('metadata.manage'))
                        <x-sidebar-link :href="route('metadata-fields.index')" :active="request()->routeIs('metadata-fields.*')" :icon="$iconFields">
                            {{ __('Metadata Fields') }}
                        </x-sidebar-link>
                    @endif
                    @if ($can('users.view'))
                        <x-sidebar-link :href="route('team.index')" :active="request()->routeIs('team.*')" :icon="$iconUsers">
                            {{ __('Team') }}
                        </x-sidebar-link>
                    @endif
                    @if ($can('api.tokens'))
                        <x-sidebar-link :href="route('api-tokens.index')" :active="request()->routeIs('api-tokens.*')" :icon="$iconShield">
                            {{ __('API Tokens') }}
                        </x-sidebar-link>
                    @endif
                    <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" :icon="$iconUser">
                        {{ __('Profile') }}
                    </x-sidebar-link>
                </div>
            </div>
        @endif
    </nav>

    <div class="shrink-0 border-t border-slate-800 p-4 bg-slate-900">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-semibold shrink-0">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium truncate">{{ $user->name }}</p>
                @if ($currentRole)
                    <p class="text-xs text-indigo-300 truncate">{{ $currentRole }}</p>
                @else
                    <p class="text-xs text-slate-400 truncate">{{ $user->email }}</p>
                @endif
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition" title="{{ __('Log Out') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
