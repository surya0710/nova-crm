<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('User Role Management') }}</h1>
    </x-slot>

    <x-flash-messages />
    @include('rbac._nav')

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('User') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Primary Role') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($members as $member)
                    <tr>
                        <td class="px-4 py-3">{{ $member->name }}<br><span class="text-xs text-slate-500">{{ $member->email }}</span></td>
                        <td class="px-4 py-3">{{ $member->getRoleNameInOrganization($organization) ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('rbac.user-roles.show', $member) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Manage Roles') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
