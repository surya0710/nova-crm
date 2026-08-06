@php
    $canManage = auth('platform')->user()->hasPermission('platform.global_users.manage');
    $navItems = [
        ['label' => __('All Users'), 'href' => route('platform.global-users.index')],
        ['label' => __('Login History'), 'href' => route('platform.global-users.login-history')],
        ['label' => __('Sessions'), 'href' => route('platform.global-users.sessions'), 'active' => true],
        ['label' => __('MFA Status'), 'href' => route('platform.global-users.mfa')],
        ['label' => __('Platform Users'), 'href' => route('platform.users.index')],
    ];
@endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Active Sessions')"
        :subtitle="__('Signed-in tenant user sessions')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Global Users'), 'href' => route('platform.global-users.index')],
                ['label' => __('Sessions'), 'current' => true],
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
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <x-forms.field :label="__('Search')" name="search" class="min-w-[16rem] flex-1">
                    <x-forms.input type="search" name="search" value="{{ $filters['search'] ?? '' }}" />
                </x-forms.field>
                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
            </form>
        </x-slot:filters>

        @if ($sessions->isEmpty())
            <x-ui.card><x-ui.empty-state-preset variant="generic" :title="__('No active sessions')" /></x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('User') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('IP Address') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium lg:table-cell">{{ __('User Agent') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Last Activity') }}</th>
                                @if ($canManage)
                                    <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($sessions as $session)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-ink-heading">{{ $session->name ?? '—' }}</div>
                                        <div class="text-xs text-ink-muted">{{ $session->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $session->ip_address ?? '—' }}</td>
                                    <td class="hidden px-4 py-3 text-xs text-ink-muted lg:table-cell">{{ \Illuminate\Support\Str::limit($session->user_agent ?? '—', 60) }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $session->last_activity ? \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() : '—' }}</td>
                                    @if ($canManage)
                                        <td class="px-4 py-3 text-right">
                                            <form method="POST" action="{{ route('platform.global-users.revoke-session') }}">
                                                @csrf
                                                <input type="hidden" name="session_id" value="{{ $session->id }}">
                                                <x-ui.button type="submit" variant="ghost" size="sm">{{ __('Revoke') }}</x-ui.button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <div class="mt-4">{{ $sessions->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
