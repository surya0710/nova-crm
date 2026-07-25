<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-slate-900">{{ __('Permission Matrix') }}</h1>
        <p class="text-sm text-slate-500">{{ __('Bulk assign permissions to roles.') }}</p>
    </x-slot>

    <x-flash-messages />
    @include('rbac._nav')

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 mb-4">
        <form method="GET" class="flex flex-wrap gap-3">
            <select name="module" class="border-gray-300 rounded-md shadow-sm text-sm">
                <option value="">{{ __('All modules') }}</option>
                @foreach ($modules as $mod)
                    <option value="{{ $mod }}" @selected($module === $mod)>{{ $mod }}</option>
                @endforeach
            </select>
            <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
        </form>
    </div>

    <form method="POST" action="{{ route('rbac.matrix.bulk') }}">
        @csrf
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="sticky left-0 bg-slate-50 px-3 py-2 text-left">{{ __('Permission') }}</th>
                        @foreach ($roles as $role)
                            <th class="px-2 py-2 text-center whitespace-nowrap">{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions as $permission)
                        <tr class="border-t border-slate-100">
                            <td class="sticky left-0 bg-white px-3 py-2 font-medium text-slate-700">{{ $permission->slug }}</td>
                            @foreach ($roles as $role)
                                @php $assigned = in_array($permission->id, $assignments[$role->id] ?? [], true); @endphp
                                <td class="px-2 py-2 text-center">
                                    <input type="checkbox"
                                           name="matrix[{{ $role->id }}][]"
                                           value="{{ $permission->id }}"
                                           @checked($assigned)
                                           class="rounded border-gray-300 text-indigo-600">
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <x-primary-button>{{ __('Save Matrix Changes') }}</x-primary-button>
        </div>
    </form>
</x-app-layout>
