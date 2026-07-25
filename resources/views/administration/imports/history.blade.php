<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Import History')"
        :subtitle="__('Search and filter past import sessions')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Import Center'), 'href' => route('administration.imports.index')],
                ['label' => __('History'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('administration.imports.index')" variant="secondary" size="sm">
                {{ __('Import Center') }}
            </x-ui.button>
        </x-slot:actions>

        <form method="GET" class="mb-6 grid gap-3 sm:grid-cols-4">
            <x-forms.field :label="__('Search filename')" name="q">
                <input type="search" name="q" value="{{ request('q') }}" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="{{ __('File name…') }}">
            </x-forms.field>
            <x-forms.field :label="__('Entity')" name="entity_type">
                <select name="entity_type" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($entityTypes as $type)
                        <option value="{{ $type }}" @selected(request('entity_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </x-forms.field>
            <x-forms.field :label="__('Status')" name="status">
                <select name="status" class="block w-full rounded-md border-line bg-surface-card text-sm text-ink shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ __('All') }}</option>
                    @foreach (['uploaded', 'ready', 'importing', 'completed', 'failed', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </x-forms.field>
            <div class="flex items-end">
                <x-ui.button type="submit" variant="secondary">{{ __('Filter') }}</x-ui.button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border border-line">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-surface-muted/50 text-left text-xs uppercase tracking-wide text-ink-muted">
                    <tr>
                        <th class="px-4 py-2">{{ __('Entity') }}</th>
                        <th class="px-4 py-2">{{ __('File') }}</th>
                        <th class="px-4 py-2">{{ __('By') }}</th>
                        <th class="px-4 py-2">{{ __('Started') }}</th>
                        <th class="px-4 py-2">{{ __('Ended') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                        <th class="px-4 py-2">{{ __('Rows') }}</th>
                        <th class="px-4 py-2">{{ __('OK / Fail / Skip') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($sessions as $session)
                        <tr>
                            <td class="px-4 py-2">
                                <a href="{{ route('administration.imports.show', $session) }}" class="font-medium text-primary-700 hover:underline">
                                    {{ $session->entity_type }}
                                </a>
                            </td>
                            <td class="px-4 py-2 text-ink">{{ $session->original_filename }}</td>
                            <td class="px-4 py-2 text-ink-muted">{{ $session->uploader?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-ink-muted">{{ $session->started_at?->toDayDateTimeString() ?? '—' }}</td>
                            <td class="px-4 py-2 text-ink-muted">{{ $session->completed_at?->toDayDateTimeString() ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <x-ui.badge variant="neutral">{{ ucfirst($session->status) }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-2 text-ink-muted">{{ $session->total_rows }}</td>
                            <td class="px-4 py-2 text-ink-muted">
                                {{ ($session->created_count + $session->updated_count) }} / {{ $session->failed_count }} / {{ $session->skipped_count }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-ink-muted">{{ __('No import sessions found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $sessions->links() }}
        </div>
    </x-layouts.entity-listing>
</x-app-layout>
