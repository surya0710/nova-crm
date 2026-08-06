@php
    $navItems = [
        ['label' => __('All Users'), 'href' => route('platform.global-users.index')],
        ['label' => __('Login History'), 'href' => route('platform.global-users.login-history')],
        ['label' => __('Sessions'), 'href' => route('platform.global-users.sessions')],
        ['label' => __('MFA Status'), 'href' => route('platform.global-users.mfa'), 'active' => true],
        ['label' => __('Platform Users'), 'href' => route('platform.users.index')],
    ];
    $hasMfaColumn = \Illuminate\Support\Facades\Schema::hasColumn('users', 'two_factor_secret');
@endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('MFA Status')"
        :subtitle="__('Two-factor authentication enrollment across tenant users')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Global Users'), 'href' => route('platform.global-users.index')],
                ['label' => __('MFA Status'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-ui.card class="mb-4">
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('Global users navigation') }}">
                @foreach ($navItems as $item)
                    <x-ui.button :href="$item['href']" :variant="($item['active'] ?? false) ? 'primary' : 'ghost'" size="sm">{{ $item['label'] }}</x-ui.button>
                @endforeach
            </nav>
        </x-ui.card>

        <x-slot:filters>
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <x-forms.field :label="__('Search')" name="search" class="sm:col-span-2">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" />
                </x-forms.field>
                @if ($hasMfaColumn)
                    <x-forms.field :label="__('MFA')" name="mfa">
                        <x-forms.select name="mfa">
                            <option value="">{{ __('All users') }}</option>
                            <option value="enabled" @selected(($filters['mfa'] ?? '') === 'enabled')>{{ __('Enabled') }}</option>
                            <option value="disabled" @selected(($filters['mfa'] ?? '') === 'disabled')>{{ __('Disabled') }}</option>
                        </x-forms.select>
                    </x-forms.field>
                @endif
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
            </form>
        </x-slot:filters>

        @if ($users->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="generic" :title="__('No users found')" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Email') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('MFA') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($users as $user)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3 font-medium text-ink-heading">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        @if ($hasMfaColumn && $user->two_factor_secret)
                                            <x-ui.badge variant="success">{{ __('Enabled') }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="neutral">{{ __('Disabled') }}</x-ui.badge>
                                        @endif
                                    </td>
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
