@php
    $canManage = auth('platform')->user()->hasPermission('platform.global_users.manage');
    $navItems = [
        ['label' => __('All Users'), 'href' => route('platform.global-users.index'), 'active' => true],
        ['label' => __('Login History'), 'href' => route('platform.global-users.login-history')],
        ['label' => __('Sessions'), 'href' => route('platform.global-users.sessions')],
        ['label' => __('MFA Status'), 'href' => route('platform.global-users.mfa')],
        ['label' => __('Platform Users'), 'href' => route('platform.users.index')],
    ];
@endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Global Users')"
        :subtitle="__('Tenant users across all organizations')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Global Users'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-4" :padding="true">
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Global users navigation') }}">
                @foreach ($navItems as $item)
                    <x-ui.button :href="$item['href']" :variant="($item['active'] ?? false) ? 'primary' : 'ghost'" size="sm">
                        {{ $item['label'] }}
                    </x-ui.button>
                @endforeach
            </nav>
        </x-ui.card>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <x-forms.field :label="__('Search')" name="search" class="sm:col-span-2">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" />
                </x-forms.field>
                <x-forms.field :label="__('Organization ID')" name="organization_id">
                    <x-forms.input type="number" name="organization_id" value="{{ $filters['organization_id'] ?? '' }}" min="1" />
                </x-forms.field>
                <x-forms.field :label="__('Locked only')" name="locked">
                    <x-forms.select name="locked">
                        <option value="">{{ __('All users') }}</option>
                        <option value="1" @selected(($filters['locked'] ?? '') === '1')>{{ __('Locked only') }}</option>
                    </x-forms.select>
                </x-forms.field>
                <div class="flex items-end gap-2 sm:col-span-4">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($users->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="generic" :title="__('No users found')" :description="__('Try adjusting your search filters.')" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Email') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium md:table-cell">{{ __('Organizations') }}</th>
                                @if ($canManage)
                                    <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($users as $user)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3 font-medium text-ink-heading">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $user->email }}</td>
                                    <td class="hidden px-4 py-3 text-ink-muted md:table-cell">
                                        {{ $user->organizations->pluck('name')->join(', ') ?: '—' }}
                                    </td>
                                    @if ($canManage)
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <form method="POST" action="{{ route('platform.global-users.password-reset', $user) }}">
                                                    @csrf
                                                    <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Reset Password') }}</x-ui.button>
                                                </form>
                                                @if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'locked_at') && $user->locked_at)
                                                    <form method="POST" action="{{ route('platform.global-users.unlock', $user) }}">
                                                        @csrf
                                                        <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Unlock') }}</x-ui.button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('platform.global-users.lock', $user) }}">
                                                        @csrf
                                                        <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Lock') }}</x-ui.button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $users->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
