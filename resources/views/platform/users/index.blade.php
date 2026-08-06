<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Platform Users')"
        :subtitle="__('Administrators with access to the platform console')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Platform Users'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('platform.users.create')" variant="primary" size="sm">{{ __('Add User') }}</x-ui.button>
        </x-slot:actions>

        @if ($users->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="generic" :title="__('No platform users')" :action-href="route('platform.users.create')" action-label="{{ __('Add User') }}" />
            </x-ui.card>
        @else
            <x-ui.card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="bg-surface-muted/50 text-ink-muted">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Name') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Email') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Role') }}</th>
                                <th scope="col" class="px-4 py-3 text-left font-medium">{{ __('Status') }}</th>
                                <th scope="col" class="hidden px-4 py-3 text-left font-medium md:table-cell">{{ __('Last Login') }}</th>
                                <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($users as $user)
                                <tr class="hover:bg-surface-muted/60">
                                    <td class="px-4 py-3 font-medium text-ink-heading">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-ink-muted">{{ $user->email }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $user->roleName() }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :variant="$user->status === 'active' ? 'success' : 'neutral'">{{ ucfirst($user->status) }}</x-ui.badge>
                                    </td>
                                    <td class="hidden px-4 py-3 text-ink-muted md:table-cell">{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <x-ui.button :href="route('platform.users.edit', $user)" variant="ghost" size="sm">{{ __('Edit') }}</x-ui.button>
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
