<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Import Summary') }}</h1>
                <p class="text-sm text-slate-500">{{ $session->original_filename }}</p>
            </div>
            <a href="{{ route('customers.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                {{ __('Back to :customers', ['customers' => crm_term('customers')]) }}
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="max-w-3xl space-y-6">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">{{ __('Created') }}</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-800">{{ $session->created_count }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Skipped') }}</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $session->skipped_count }}</p>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-rose-700">{{ __('Failed') }}</p>
                <p class="mt-1 text-2xl font-semibold text-rose-800">{{ $session->failed_count }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-amber-700">{{ __('Duplicate') }}</p>
                <p class="mt-1 text-2xl font-semibold text-amber-800">{{ $duplicateRows }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Status') }}</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ ucfirst($session->status) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Processed rows') }}</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $session->processed_rows }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Started') }}</dt>
                    <dd class="mt-1 text-slate-900">{{ $session->started_at?->toDayDateTimeString() ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Completed') }}</dt>
                    <dd class="mt-1 text-slate-900">{{ $session->completed_at?->toDayDateTimeString() ?? '—' }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('customers.index') }}" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    {{ __('View :customers', ['customers' => crm_term('customers')]) }}
                </a>
                <a href="{{ route('customers.import.create') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('Import another file') }}
                </a>
                @if ($session->failed_count > 0 || $duplicateRows > 0)
                    <a href="{{ route('customers.import.errors', $session) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('Download error report') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
