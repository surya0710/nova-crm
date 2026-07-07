<x-platform-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ $organization->name }}</h1>
            <a href="{{ route('platform.organizations.index') }}" class="text-sm text-slate-400 hover:text-white">{{ __('Back to list') }}</a>
        </div>
    </x-slot>

    @php $platformUser = auth('platform')->user(); @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-4">
            <h2 class="font-medium text-white">{{ __('Basic Information') }}</h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">{{ __('Email') }}</dt><dd class="text-slate-200">{{ $organization->email ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Slug') }}</dt><dd class="text-slate-200">{{ $organization->slug }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Plan') }}</dt><dd class="text-slate-200">{{ $organization->planLabel() }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Status') }}</dt><dd class="text-slate-200">{{ $organization->status->label() }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Owner') }}</dt><dd class="text-slate-200">{{ $owner?->name ?? '—' }} ({{ $owner?->email }})</dd></div>
                <div><dt class="text-slate-500">{{ __('Users') }}</dt><dd class="text-slate-200">{{ $organization->users_count }}</dd></div>
                <div><dt class="text-slate-500">{{ __('Storage') }}</dt><dd class="text-slate-200">{{ number_format($organization->storage_used_bytes / 1048576, 2) }} MB</dd></div>
                <div><dt class="text-slate-500">{{ __('API Tokens') }}</dt><dd class="text-slate-200">{{ $api_tokens }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl bg-slate-900 border border-slate-800 p-6 space-y-3">
            <h2 class="font-medium text-white">{{ __('Actions') }}</h2>
            @if ($platformUser->hasPermission('platform.organizations.manage'))
                @if ($organization->isActive())
                    <form method="POST" action="{{ route('platform.organizations.suspend', $organization) }}">@csrf<button class="w-full rounded-lg bg-amber-600/20 text-amber-300 border border-amber-700 px-4 py-2 text-sm">{{ __('Suspend') }}</button></form>
                    <form method="POST" action="{{ route('platform.organizations.archive', $organization) }}" onsubmit="return confirm('{{ __('Archive this organization?') }}')">@csrf<button class="w-full rounded-lg bg-slate-700 text-slate-200 px-4 py-2 text-sm">{{ __('Archive') }}</button></form>
                @elseif ($organization->isSuspended())
                    <form method="POST" action="{{ route('platform.organizations.activate', $organization) }}">@csrf<button class="w-full rounded-lg bg-emerald-600/20 text-emerald-300 border border-emerald-700 px-4 py-2 text-sm">{{ __('Activate') }}</button></form>
                @elseif ($organization->isArchived())
                    <form method="POST" action="{{ route('platform.organizations.activate', $organization) }}">@csrf<button class="w-full rounded-lg bg-emerald-600/20 text-emerald-300 border border-emerald-700 px-4 py-2 text-sm">{{ __('Reactivate') }}</button></form>
                @endif
            @endif
            @if ($platformUser->hasPermission('platform.impersonate') && $organization->isActive())
                <form method="POST" action="{{ route('platform.organizations.impersonate', $organization) }}">@csrf<button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 text-sm">{{ __('Login As Organization') }}</button></form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        @foreach (['leads' => __('Leads'), 'customers' => __('Customers'), 'opportunities' => __('Opportunities'), 'invoices' => __('Invoices'), 'revenue_managed' => __('Revenue')] as $key => $label)
            <div class="rounded-xl bg-slate-900 border border-slate-800 p-4">
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div class="text-xl font-semibold mt-1">
                    @if ($key === 'revenue_managed')
                        {{ number_format($counts[$key], 2) }}
                    @else
                        {{ number_format($counts[$key]) }}
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-800 font-medium">{{ __('Recent Audit') }}</div>
            <div class="divide-y divide-slate-800 text-sm">
                @forelse ($recent_audit as $log)
                    <div class="px-5 py-3">
                        <div class="text-white">{{ $log->event }} — {{ $log->subject }}</div>
                        <div class="text-slate-500 text-xs">{{ $log->user?->name }} · {{ $log->created_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <div class="px-5 py-6 text-slate-500 text-center">{{ __('No audit entries.') }}</div>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-800 font-medium">{{ __('Recent Logins') }}</div>
            <div class="divide-y divide-slate-800 text-sm">
                @forelse ($recent_logins as $login)
                    <div class="px-5 py-3">
                        <div class="text-white">{{ $login->name }}</div>
                        <div class="text-slate-500 text-xs">{{ $login->email }}</div>
                    </div>
                @empty
                    <div class="px-5 py-6 text-slate-500 text-center">{{ __('No login data.') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</x-platform-layout>
