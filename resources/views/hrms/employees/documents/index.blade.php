@php
    $density = $shellNav['density'] ?? 'comfortable';
    $verificationVariant = [
        'pending' => 'warning',
        'verified' => 'success',
        'rejected' => 'danger',
    ];
    $columns = [__('Title'), __('Category'), __('Verification'), __('Expiry'), __('Version'), __('Actions')];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Documents')"
        :subtitle="$employee->full_name . ' · ' . $employee->employee_code"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('HR'), 'href' => route('hrms.home')],
                ['label' => __('Employees'), 'href' => route('hrms.employees.index')],
                ['label' => $employee->full_name, 'href' => route('hrms.employees.show', $employee)],
                ['label' => __('Documents'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('hrms.employees.show', $employee)" variant="secondary" size="sm">{{ __('Back to Employee') }}</x-ui.button>
            @can('manage', App\Models\EmployeeDocument::class)
                <x-ui.button :href="route('hrms.employees.documents.create', $employee)" variant="primary" size="sm">{{ __('Upload Document') }}</x-ui.button>
            @endcan
        </x-slot:actions>

        @if ($documents->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset
                    variant="documents"
                    :action-href="auth()->user()->can('manage', App\Models\EmployeeDocument::class) ? route('hrms.employees.documents.create', $employee) : null"
                />
            </x-ui.card>
        @else
            <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                @foreach ($documents as $document)
                    <tr class="hover:bg-surface-muted/60 transition">
                        <td class="px-4 py-3 text-sm font-medium text-ink-heading">{{ $document->title }}</td>
                        <td class="px-4 py-3 text-sm text-ink-muted">{{ $document->categoryLabel() }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$verificationVariant[$document->verification_status] ?? 'neutral'">{{ $document->verificationStatusLabel() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">
                            @if ($document->expires_at)
                                {{ $document->expires_at->format('Y-m-d') }}
                                @if ($document->isExpired())
                                    <span class="text-danger text-xs">({{ __('Expired') }})</span>
                                @elseif ($document->isExpiringSoon())
                                    <span class="text-warning text-xs">({{ __('Expiring soon') }})</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-ink-muted">v{{ $document->currentVersion?->version_no ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.button :href="route('hrms.employees.documents.show', [$employee, $document])" variant="link" size="sm">{{ __('View') }}</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-tables.table>
        @endif

        @if ($documents->hasPages())
            <x-slot:pagination>{{ $documents->links() }}</x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
