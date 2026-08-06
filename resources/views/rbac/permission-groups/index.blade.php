<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Permission Groups') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Organize permissions into logical groups.') }}</p>
            </div>
            @can('createGroup', App\Models\PermissionGroup::class)
                <a href="{{ route('rbac.permission-groups.create') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">{{ __('New Group') }}</a>
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
                        <th class="px-4 py-3 text-left">{{ __('Group') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Sort') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($groups as $group)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ $group->name }}</p>
                                <p class="text-xs text-slate-500">{{ $group->slug }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $group->sort_order }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $group->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $group->is_active ? __('Active') : __('Archived') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @can('updateGroup', $group)
                                    <a href="{{ route('rbac.permission-groups.edit', $group) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
