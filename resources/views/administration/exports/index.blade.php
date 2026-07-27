<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Export Center')"
        :subtitle="__('Generate secure Excel, CSV, and PDF exports across every module')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Export Center'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('administration.exports.history')" variant="secondary" size="sm">
                {{ __('Export history') }}
            </x-ui.button>
        </x-slot:actions>

        <div class="space-y-8">
            @forelse ($groups as $module => $entities)
                <x-entity.section :title="__($moduleLabels[$module] ?? ucfirst($module))">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($entities as $entity)
                            <a
                                href="{{ route('administration.exports.create', $entity['type']) }}"
                                class="block rounded-lg border border-line bg-surface px-4 py-4 transition hover:border-primary-300 hover:bg-primary-50/40"
                            >
                                <p class="font-medium text-ink-heading">{{ __($entity['label']) }}</p>
                                <p class="mt-1 text-xs text-ink-muted">
                                    {{ __(':count columns · Excel, CSV & PDF', ['count' => $entity['column_count']]) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </x-entity.section>
            @empty
                <x-ui.empty-state
                    :title="__('No exports available')"
                    :description="__('Your role or licensed modules do not include any exportable entities.')"
                />
            @endforelse

            <x-entity.section :title="__('Recent exports')">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-line text-sm">
                        <thead class="text-left text-xs uppercase text-ink-muted">
                            <tr>
                                <th class="px-3 py-2">{{ __('Entity') }}</th>
                                <th class="px-3 py-2">{{ __('Format') }}</th>
                                <th class="px-3 py-2">{{ __('Status') }}</th>
                                <th class="px-3 py-2">{{ __('Records') }}</th>
                                <th class="px-3 py-2">{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($recent as $session)
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('administration.exports.show', $session) }}" class="text-primary-700 hover:underline">
                                            {{ config('export.entities.'.$session->entity_type.'.label', $session->entity_type) }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2">{{ strtoupper($session->format) }}</td>
                                    <td class="px-3 py-2">{{ ucfirst($session->status) }}</td>
                                    <td class="px-3 py-2">{{ $session->processed_count }}/{{ $session->total_count }}</td>
                                    <td class="px-3 py-2 text-ink-muted">{{ $session->initiator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-ink-muted">{{ __('No exports yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-entity.section>
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
