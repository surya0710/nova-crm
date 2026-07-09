<div class="flex flex-col h-full">
    <div class="p-5 border-b border-slate-800">
        <div class="flex items-center gap-2">
            <div class="h-8 w-8 rounded-lg bg-violet-600 flex items-center justify-center text-xs font-bold">P</div>
            <span class="font-semibold text-white">Platform</span>
        </div>
    </div>

    <nav class="flex-1 p-3 space-y-1 text-sm">
        @php $user = auth('platform')->user(); @endphp

        @if ($user->hasPermission('platform.dashboard'))
            <a href="{{ route('platform.dashboard') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('platform.dashboard') ? 'bg-violet-600/20 text-violet-300' : 'text-slate-300 hover:bg-slate-800' }}">
                {{ __('Dashboard') }}
            </a>
        @endif

        @if ($user->hasPermission('platform.organizations.view'))
            <a href="{{ route('platform.organizations.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('platform.organizations.*') ? 'bg-violet-600/20 text-violet-300' : 'text-slate-300 hover:bg-slate-800' }}">
                {{ __('Organizations') }}
            </a>
        @endif

        @if ($user->hasPermission('platform.industry_templates.view'))
            <a href="{{ route('platform.industry-templates.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('platform.industry-templates.*') || request()->routeIs('platform.industry-template-versions.*') ? 'bg-violet-600/20 text-violet-300' : 'text-slate-300 hover:bg-slate-800' }}">
                {{ __('Industry Templates') }}
            </a>
        @endif

        @if ($user->hasPermission('platform.reports.view'))
            <a href="{{ route('platform.reports.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('platform.reports.*') ? 'bg-violet-600/20 text-violet-300' : 'text-slate-300 hover:bg-slate-800' }}">
                {{ __('Reports') }}
            </a>
        @endif

        @if ($user->hasPermission('platform.audit.view'))
            <a href="{{ route('platform.audit.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('platform.audit.*') ? 'bg-violet-600/20 text-violet-300' : 'text-slate-300 hover:bg-slate-800' }}">
                {{ __('Audit Log') }}
            </a>
        @endif

        @if ($user->hasPermission('platform.users.manage'))
            <a href="{{ route('platform.users.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('platform.users.*') ? 'bg-violet-600/20 text-violet-300' : 'text-slate-300 hover:bg-slate-800' }}">
                {{ __('Platform Users') }}
            </a>
        @endif
    </nav>

    <div class="p-4 border-t border-slate-800">
        <form method="POST" action="{{ route('platform.logout') }}">
            @csrf
            <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-slate-400 hover:bg-slate-800 text-sm">
                {{ __('Sign out') }}
            </button>
        </form>
    </div>
</div>
