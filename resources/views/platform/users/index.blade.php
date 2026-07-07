<x-platform-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-lg font-semibold">{{ __('Platform Users') }}</h1>
            <a href="{{ route('platform.users.create') }}" class="text-sm rounded-lg bg-violet-600 hover:bg-violet-500 px-3 py-1.5">{{ __('Add User') }}</a>
        </div>
    </x-slot>

    <div class="rounded-xl bg-slate-900 border border-slate-800 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-800/50 text-slate-400">
                <tr>
                    <th class="px-4 py-3 text-left">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Email') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Role') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Last Login') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3 text-white">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $user->roleName() }}</td>
                        <td class="px-4 py-3 text-slate-300 capitalize">{{ $user->status }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('platform.users.edit', $user) }}" class="text-violet-400 hover:text-violet-300">{{ __('Edit') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-platform-layout>
