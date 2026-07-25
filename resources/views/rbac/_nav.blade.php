<nav class="flex flex-wrap gap-2 mb-6">
    @foreach ([
        'rbac.roles.index' => __('Roles'),
        'rbac.permissions.index' => __('Permissions'),
        'rbac.permission-groups.index' => __('Groups'),
        'rbac.matrix.index' => __('Matrix'),
        'rbac.user-roles.index' => __('User Roles'),
        'rbac.templates.index' => __('Templates'),
    ] as $route => $label)
        <a href="{{ route($route) }}"
           class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs(str_replace('.index', '.*', $route)) || request()->routeIs($route) ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
