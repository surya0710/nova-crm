<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Roles') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Manage organization roles and hierarchy.') }}</p>
            </div>
            @can('createRole', App\Models\Role::class)
                <a href="{{ route('rbac.roles.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                    {{ __('New Role') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <x-flash-messages />
    @include('rbac._nav')

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Role') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Level') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-600">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($roles as $role)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full" style="background: {{ $role->color ?? '#6366f1' }}"></span>
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $role->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $role->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $role->hierarchy_level }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $role->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $role->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                @can('updateRole', $role)
                                    <a href="{{ route('rbac.roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                                    <form action="{{ route('rbac.roles.duplicate', $role) }}" method="POST" class="inline">@csrf<button type="submit" class="text-slate-600 hover:text-slate-800">{{ __('Duplicate') }}</button></form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No roles found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
