<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Team') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage organization members and roles') }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="max-w-5xl space-y-6">
        @can('create', App\Models\User::class)
            <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-semibold text-slate-900">{{ __('Add Team Member') }}</h3>
                    <p class="text-sm text-slate-500 mt-0.5">{{ __('Create a new account or add an existing user by email.') }}</p>
                </div>
                <form method="POST" action="{{ route('team.store') }}" class="p-6 sm:p-8">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="name" :value="__('Full Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">{{ __('Select role…') }}</option>
                                @foreach ($assignableRoles as $role)
                                    <option value="{{ $role->slug }}" @selected(old('role') === $role->slug)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" autocomplete="new-password" />
                            <p class="mt-1 text-xs text-slate-500">{{ __('Required for new accounts. Leave blank if the user already exists.') }}</p>
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                            <x-text-input id="password_confirmation" class="block mt-1 w-full sm:max-w-md" type="password" name="password_confirmation" autocomplete="new-password" />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <x-primary-button>{{ __('Add Member') }}</x-primary-button>
                    </div>
                </form>
            </div>
        @endcan

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-semibold text-slate-900">{{ __('Members') }}</h3>
                <p class="text-sm text-slate-500 mt-0.5">{{ __(':count members in :org', ['count' => $members->count(), 'org' => $organization->name]) }}</p>
            </div>

            @if ($members->isEmpty())
                <div class="p-12 text-center text-sm text-slate-500">{{ __('No team members yet.') }}</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Member') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Role') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 hidden sm:table-cell">{{ __('Joined') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($members as $member)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-semibold shrink-0">
                                                {{ strtoupper(substr($member->name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-900 truncate">
                                                    {{ $member->name }}
                                                    @if ($member->id === Auth::id())
                                                        <span class="text-xs font-normal text-slate-400">({{ __('you') }})</span>
                                                    @endif
                                                </p>
                                                <p class="text-xs text-slate-500 truncate">{{ $member->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($member->isOwnerOf($organization))
                                            <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800">
                                                {{ $member->organizationRole?->name ?? __('Organization Owner') }}
                                            </span>
                                        @elseif (Auth::user()->can('update', $member))
                                            <form method="POST" action="{{ route('team.update', $member) }}" class="inline-flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" onchange="this.form.submit()" class="text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm py-1.5">
                                                    @foreach ($assignableRoles as $role)
                                                        <option value="{{ $role->slug }}" @selected($member->organizationRole?->slug === $role->slug)>{{ $role->name }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @else
                                            <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                                {{ $member->organizationRole?->name ?? '—' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 hidden sm:table-cell text-sm text-slate-500">
                                        {{ $member->pivot->created_at?->format('M j, Y') ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @can('delete', $member)
                                            <form method="POST" action="{{ route('team.destroy', $member) }}" class="inline" onsubmit="return confirm('{{ __('Remove this member from the organization?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                                            </form>
                                        @else
                                            <span class="text-sm text-slate-300">—</span>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
