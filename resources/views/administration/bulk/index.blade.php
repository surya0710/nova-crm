<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Bulk Operations')"
        :subtitle="__('Run secure, auditable actions across many records')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Bulk Operations'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('administration.bulk.history')" variant="secondary" size="sm">
                {{ __('History') }}
            </x-ui.button>
        </x-slot:actions>

        <div class="space-y-8">
            <x-ui.alert variant="info">
                {{ __('Open a module listing (Leads, Employees, Tasks, Users) and use the bulk toolbar to select records and run actions.') }}
            </x-ui.alert>

            @foreach ($groups as $module => $actions)
                <x-entity.section :title="__($moduleLabels[$module] ?? ucfirst($module))">
                    <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 text-sm">
                        @foreach ($actions as $action)
                            <li class="rounded-lg border border-line px-3 py-2">
                                <p class="font-medium text-ink-heading">{{ $action['label'] }}</p>
                                <p class="text-xs text-ink-muted">{{ $action['entity_type'] }} · {{ $action['key'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-entity.section>
            @endforeach

            <x-entity.section :title="__('Recent operations')">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="text-left text-xs uppercase text-ink-muted">
                            <tr>
                                <th class="px-3 py-2">{{ __('Action') }}</th>
                                <th class="px-3 py-2">{{ __('Status') }}</th>
                                <th class="px-3 py-2">{{ __('Progress') }}</th>
                                <th class="px-3 py-2">{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($recent as $operation)
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('administration.bulk.show', $operation) }}" class="text-primary-700 hover:underline">
                                            {{ $operation->action_key }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2">{{ ucfirst($operation->status) }}</td>
                                    <td class="px-3 py-2">{{ $operation->processed_count }}/{{ $operation->total_count }}</td>
                                    <td class="px-3 py-2 text-ink-muted">{{ $operation->initiator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-ink-muted">{{ __('No bulk operations yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-entity.section>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
