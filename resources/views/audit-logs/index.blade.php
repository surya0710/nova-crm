<x-app-layout>
    <x-ui.page-header
        :title="__('Audit Log')"
        :subtitle="__('Organization activity and change history')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => \Illuminate\Support\Facades\Route::has('administration.home') ? route('administration.home') : null],
                ['label' => __('Audit Log'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>
    </x-ui.page-header>

    <x-flash-messages />

    <div class="rounded-xl bg-surface-card border border-line shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-forms.input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search subject or user…') }}" class="w-full sm:col-span-2" />
            <div class="flex gap-2">
                <x-forms.select name="event" class="flex-1 text-sm">
                    <option value="">{{ __('All events') }}</option>
                    @foreach (['created', 'updated', 'deleted', 'status_changed', 'assigned', 'uploaded'] as $event)
                        <option value="{{ $event }}" @selected(($filters['event'] ?? '') === $event)>{{ ucfirst(str_replace('_', ' ', $event)) }}</option>
                    @endforeach
                </x-forms.select>
                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-surface-card border border-line shadow-sm overflow-hidden">
        @if ($logs->isEmpty())
            <div class="p-8">
                <x-ui.empty-state-preset variant="admin_audit" />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-surface-muted/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-ink-muted">{{ __('When') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-ink-muted">{{ __('User') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-ink-muted">{{ __('Event') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-ink-muted">{{ __('Subject') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-surface-muted/40">
                                <td class="px-6 py-4 text-sm text-ink-muted whitespace-nowrap">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-6 py-4 text-sm text-ink">{{ $log->user?->name ?? __('System') }}</td>
                                <td class="px-6 py-4 text-sm"><x-ui.badge variant="neutral">{{ $log->event_label }}</x-ui.badge></td>
                                <td class="px-6 py-4 text-sm text-ink-heading">{{ $log->subject }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="px-6 py-4 border-t">{{ $logs->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
