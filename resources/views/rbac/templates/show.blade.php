<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Template Preview') }} — {{ $preview['template']->name }}</h1>
    </x-slot>

    <x-flash-messages />
    @include('rbac._nav')

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
        <p class="text-sm text-slate-600 mb-4">{{ $preview['template']->description }}</p>
        <p class="text-sm font-medium text-slate-900 mb-4">{{ __(':count unique permissions', ['count' => $preview['permission_count']]) }}</p>

        <div class="space-y-4">
            @foreach ($preview['roles'] as $role)
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="font-semibold text-slate-900">{{ $role->role_name }}</span>
                        <span class="text-xs text-slate-500">({{ $role->role_slug }}) — L{{ $role->hierarchy_level }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($role->templatePermissions as $perm)
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-600">{{ $perm->permission_slug }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
