<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Permissions') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Browse and manage organization permissions.') }}</p>
            </div>
        </div>
    </x-slot>

    <x-flash-messages />
    @include('rbac._nav')

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search…') }}" class="w-full" />
            <select name="group_id" class="border-gray-300 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All groups') }}</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected(($filters['group_id'] ?? '') == $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
            <x-text-input name="module" :value="$filters['module'] ?? ''" placeholder="{{ __('Module') }}" class="w-full" />
            <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left">{{ __('Permission') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Module') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Group') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($permissions as $permission)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-900">{{ $permission->name }}</p>
                                <p class="text-xs text-slate-500">{{ $permission->slug }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $permission->module }}</td>
                            <td class="px-4 py-3">{{ $permission->group?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $permission->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $permission->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
