<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Export history')"
        :subtitle="__('Search and review generated exports for your organization')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Export Center'), 'href' => route('administration.exports.index')],
                ['label' => __('History'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('administration.exports.index')" variant="secondary" size="sm">
                {{ __('Back to catalog') }}
            </x-ui.button>
        </x-slot:actions>

        <form method="get" class="mb-4 flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Search…') }}" class="rounded-md border-line bg-surface-card text-sm">
            <select name="entity_type" class="rounded-md border-line bg-surface-card text-sm">
                <option value="">{{ __('All entities') }}</option>
                @foreach (config('export.entities', []) as $type => $meta)
                    <option value="{{ $type }}" @selected(request('entity_type') === $type)>{{ $meta['label'] ?? $type }}</option>
                @endforeach
            </select>
            <select name="format" class="rounded-md border-line bg-surface-card text-sm">
                <option value="">{{ __('All formats') }}</option>
                @foreach ($formats as $key => $meta)
                    <option value="{{ $key }}" @selected(request('format') === $key)>{{ $meta['label'] ?? strtoupper($key) }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-md border-line bg-surface-card text-sm">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['pending','queued','running','completed','failed','revoked','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-ui.button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-line">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-surface-muted/40 text-left text-xs uppercase text-ink-muted">
                    <tr>
                        <th class="px-3 py-2">{{ __('Entity') }}</th>
                        <th class="px-3 py-2">{{ __('Type') }}</th>
                        <th class="px-3 py-2">{{ __('Format') }}</th>
                        <th class="px-3 py-2">{{ __('Status') }}</th>
                        <th class="px-3 py-2">{{ __('Records') }}</th>
                        <th class="px-3 py-2">{{ __('Size') }}</th>
                        <th class="px-3 py-2">{{ __('By') }}</th>
                        <th class="px-3 py-2">{{ __('Started') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($sessions as $session)
                        <tr>
                            <td class="px-3 py-2">
                                <a href="{{ route('administration.exports.show', $session) }}" class="text-primary-700 hover:underline">
                                    {{ config('export.entities.'.$session->entity_type.'.label', $session->entity_type) }}
                                </a>
                            </td>
                            <td class="px-3 py-2">{{ $session->selection_mode }}</td>
                            <td class="px-3 py-2">{{ strtoupper($session->format) }}</td>
                            <td class="px-3 py-2">{{ ucfirst($session->status) }}</td>
                            <td class="px-3 py-2">{{ $session->processed_count }}/{{ $session->total_count }}</td>
                            <td class="px-3 py-2">{{ $session->formattedFileSize() }}</td>
                            <td class="px-3 py-2 text-ink-muted">{{ $session->initiator?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-ink-muted">{{ $session->started_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-8 text-center text-ink-muted">{{ __('No export history found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $sessions->links() }}</div>
    </x-layouts.entity-listing>
</x-app-layout>
