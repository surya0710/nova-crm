<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Roles for :name', ['name' => $member->name]) }}</h1>
    </x-slot>

    <x-flash-messages />
    @include('rbac._nav')

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Assign Roles') }}</h2>
            <form method="POST" action="{{ route('rbac.user-roles.sync', $member) }}" class="space-y-4">
                @csrf
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach ($availableRoles as $role)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                                   @checked($assignedRoles->contains('id', $role->id))
                                   class="rounded border-gray-300 text-indigo-600">
                            {{ $role->name }}
                        </label>
                    @endforeach
                </div>
                <div>
                    <x-input-label for="primary_role_id" :value="__('Primary Role')" />
                    <select id="primary_role_id" name="primary_role_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">{{ __('Select primary role') }}</option>
                        @foreach ($availableRoles as $role)
                            <option value="{{ $role->id }}" @selected($member->getRoleInOrganization($organization)?->id === $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>{{ __('Save Roles') }}</x-primary-button>
            </form>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Effective Permissions') }} ({{ $effectivePermissions->count() }})</h2>
            <div class="max-h-96 overflow-y-auto text-xs text-slate-600 space-y-1">
                @foreach ($effectivePermissions as $permission)
                    <div class="rounded bg-slate-50 px-2 py-1 font-mono">{{ $permission }}</div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
