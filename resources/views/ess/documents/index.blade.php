@php
    $density = $shellNav['density'] ?? 'comfortable';
    $columns = [
        __('Title'),
        __('Category'),
        __('Verification'),
        __('Expires'),
        __('Actions'),
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('My Documents')"
        :subtitle="__('View and download your HR documents')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('My HR'), 'href' => route('ess.dashboard')],
                ['label' => __('Documents'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @include('ess.partials.nav')

        @if ($documents->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="documents" />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($documents as $document)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('ess.documents.show', $document) }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">{{ $document->title }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ config('hrms.document_categories.'.$document->category, $document->category) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ config('hrms.document_verification_statuses.'.$document->verification_status, $document->verification_status) }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $document->expires_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.button :href="route('ess.documents.download', $document)" variant="ghost" size="sm">{{ __('Download') }}</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
            <div class="mt-4">{{ $documents->links() }}</div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
