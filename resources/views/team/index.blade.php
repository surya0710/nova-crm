<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Users')"
        :subtitle="__('Manage organization members and roles')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => \Illuminate\Support\Facades\Route::has('administration.home') ? route('administration.home') : null],
                ['label' => __('Users'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="space-y-6">
            @can('create', App\Models\User::class)
                <x-ui.card>
                    <x-slot:header>
                        <div>
                            <h3 class="font-semibold text-ink-heading">{{ __('Invite User') }}</h3>
                            <p class="text-sm text-ink-muted mt-0.5">{{ __('Invite a new teammate or add an existing user by email. They will set their own password.') }}</p>
                        </div>
                    </x-slot:header>
                    <form id="invite" method="POST" action="{{ route('team.store') }}" class="space-y-5">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <x-forms.field :label="__('Full Name')" name="name">
                                <x-forms.input id="name" name="name" :value="old('name')" required />
                            </x-forms.field>
                            <x-forms.field :label="__('Email')" name="email">
                                <x-forms.input id="email" type="email" name="email" :value="old('email')" required />
                            </x-forms.field>
                            <x-forms.field :label="__('Role')" name="role">
                                <x-forms.select id="role" name="role" required>
                                    <option value="">{{ __('Select role…') }}</option>
                                    @foreach ($assignableRoles as $role)
                                        <option value="{{ $role->slug }}" @selected(old('role') === $role->slug)>{{ $role->name }}</option>
                                    @endforeach
                                </x-forms.select>
                            </x-forms.field>
                            <div class="sm:col-span-2">
                                <p class="text-sm text-ink-muted">{{ __('New users receive an invitation email to set their own password. Administrators never assign passwords.') }}</p>
                                <label class="mt-2 inline-flex items-center gap-2 text-sm text-ink-heading">
                                    <input type="checkbox" name="send_invitation" value="1" @checked(old('send_invitation', true)) class="rounded border-line text-primary-600 focus:ring-primary-500">
                                    {{ __('Send invitation email') }}
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <x-ui.button type="submit" variant="primary">{{ __('Send Invitation') }}</x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            @endcan

            <x-ui.card :padding="false">
                <div class="px-5 py-4 border-b border-line">
                    <h3 class="font-semibold text-ink-heading">{{ __('Members') }}</h3>
                    <p class="text-sm text-ink-muted mt-0.5">{{ __(':count members in :org', ['count' => $members->count(), 'org' => $organization->name]) }}</p>
                </div>

                @if ($members->isEmpty())
                    <div class="p-8">
                        <x-ui.empty-state-preset
                            variant="users"
                            :action-href="auth()->user()->can('create', App\Models\User::class) ? route('team.index').'#invite' : null"
                        />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-line">
                            <thead class="bg-surface-muted/50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Member') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Role') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted hidden md:table-cell">{{ __('Account') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted hidden lg:table-cell">{{ __('Portal') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted hidden lg:table-cell">{{ __('Last login') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-ink-muted hidden sm:table-cell">{{ __('Joined') }}</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($members as $member)
                                    <tr class="hover:bg-surface-muted/40">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="h-9 w-9 rounded-full bg-primary-50 text-primary-700 flex items-center justify-center text-sm font-semibold shrink-0">
                                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-ink-heading truncate">
                                                        {{ $member->name }}
                                                        @if ($member->id === Auth::id())
                                                            <span class="text-xs font-normal text-ink-muted">({{ __('you') }})</span>
                                                        @endif
                                                    </p>
                                                    <p class="text-xs text-ink-muted truncate">{{ $member->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            @if ($member->isOwnerOf($organization))
                                                <x-ui.badge variant="primary">
                                                    {{ $member->organizationRole?->name ?? __('Organization Owner') }}
                                                </x-ui.badge>
                                            @elseif (Auth::user()->can('update', $member))
                                                <form method="POST" action="{{ route('team.update', $member) }}" class="inline-flex items-center gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <x-forms.select name="role" onchange="this.form.submit()" class="text-sm py-1.5">
                                                        @foreach ($assignableRoles as $role)
                                                            <option value="{{ $role->slug }}" @selected($member->organizationRole?->slug === $role->slug)>{{ $role->name }}</option>
                                                        @endforeach
                                                    </x-forms.select>
                                                </form>
                                            @else
                                                <x-ui.badge variant="neutral">
                                                    {{ $member->organizationRole?->name ?? '—' }}
                                                </x-ui.badge>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 hidden md:table-cell">
                                            <x-ui.badge variant="neutral">{{ $member->displayAccountStatusLabel() }}</x-ui.badge>
                                            @if ($member->employees->isNotEmpty())
                                                <p class="mt-1 text-xs text-ink-muted">{{ __('Linked:') }} {{ $member->employees->first()->full_name }}</p>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 hidden lg:table-cell text-sm text-ink-muted">
                                            {{ $member->portal_access_enabled ? __('Enabled') : __('Disabled') }}
                                        </td>
                                        <td class="px-5 py-4 hidden lg:table-cell text-sm text-ink-muted">
                                            {{ $member->last_login_at?->format('M j, Y') ?? '—' }}
                                        </td>
                                        <td class="px-5 py-4 hidden sm:table-cell text-sm text-ink-muted">
                                            {{ $member->pivot->created_at?->format('M j, Y') ?? '—' }}
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            @can('delete', $member)
                                                <form method="POST" action="{{ route('team.destroy', $member) }}" class="inline" onsubmit="return confirm('{{ __('Remove this member from the organization?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-danger hover:opacity-80">{{ __('Remove') }}</button>
                                                </form>
                                            @else
                                                <span class="text-sm text-ink-muted">—</span>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
