<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Audit Log') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Organization activity and change history') }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-text-input name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search subject or user…') }}" class="w-full sm:col-span-2" />
            <div class="flex gap-2">
                <select name="event" class="flex-1 border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All events') }}</option>
                    @foreach (['created', 'updated', 'deleted', 'status_changed', 'assigned', 'uploaded'] as $event)
                        <option value="{{ $event }}" @selected(($filters['event'] ?? '') === $event)>{{ ucfirst(str_replace('_', ' ', $event)) }}</option>
                    @endforeach
                </select>
                <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($logs->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No activity recorded yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('When') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('User') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('Event') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">{{ __('Subject') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($logs as $log)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $log->user?->name ?? __('System') }}</td>
                                <td class="px-6 py-4 text-sm"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $log->event_label }}</span></td>
                                <td class="px-6 py-4 text-sm text-slate-900">{{ $log->subject }}</td>
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
