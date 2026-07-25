<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Bulk History')"
        :subtitle="__('Past bulk operations for this organization')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Bulk Operations'), 'href' => route('administration.bulk.index')],
                ['label' => __('History'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <form method="GET" class="mb-4 grid gap-3 sm:grid-cols-3">
            <select name="entity_type" class="block w-full rounded-md border-line bg-surface-card text-sm">
                <option value="">{{ __('All entities') }}</option>
                @foreach (array_keys(config('bulk.entities', [])) as $type)
                    <option value="{{ $type }}" @selected(request('entity_type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <select name="status" class="block w-full rounded-md border-line bg-surface-card text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['pending', 'queued', 'running', 'completed', 'failed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="secondary">{{ __('Filter') }}</x-ui.button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-line">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-surface-muted/50 text-left text-xs uppercase text-ink-muted">
                    <tr>
                        <th class="px-4 py-2">{{ __('Action') }}</th>
                        <th class="px-4 py-2">{{ __('Entity') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                        <th class="px-4 py-2">{{ __('Counts') }}</th>
                        <th class="px-4 py-2">{{ __('By') }}</th>
                        <th class="px-4 py-2">{{ __('Started') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($operations as $operation)
                        <tr>
                            <td class="px-4 py-2">
                                <a class="text-primary-700 hover:underline" href="{{ route('administration.bulk.show', $operation) }}">
                                    {{ $operation->action_key }}
                                </a>
                            </td>
                            <td class="px-4 py-2">{{ $operation->entity_type }}</td>
                            <td class="px-4 py-2">{{ ucfirst($operation->status) }}</td>
                            <td class="px-4 py-2 text-ink-muted">
                                {{ $operation->success_count }}/{{ $operation->failed_count }}/{{ $operation->skipped_count }}
                            </td>
                            <td class="px-4 py-2 text-ink-muted">{{ $operation->initiator?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-ink-muted">{{ $operation->started_at?->toDayDateTimeString() ?? $operation->created_at?->toDayDateTimeString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-ink-muted">{{ __('No operations found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $operations->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
