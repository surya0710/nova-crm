<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Import Preview') }}</h1>
                <p class="text-sm text-slate-500">{{ $session->original_filename }}</p>
            </div>
            <a href="{{ route('leads.import.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                {{ __('Upload another file') }}
            </a>
        </div>
    </x-slot>

    <x-flash-messages />

    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Rows') }}</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $preview->totalRows }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">{{ __('Valid') }}</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-800">{{ $preview->validRows }}</p>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-rose-700">{{ __('Invalid') }}</p>
                <p class="mt-1 text-2xl font-semibold text-rose-800">{{ $preview->invalidRows }}</p>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-rose-700">{{ __('Errors') }}</p>
                <p class="mt-1 text-2xl font-semibold text-rose-800">{{ count($preview->errors) }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-amber-700">{{ __('Duplicates') }}</p>
                <p class="mt-1 text-2xl font-semibold text-amber-800">{{ (int) ($session->validation_summary['duplicate_rows'] ?? 0) }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Column mapping') }}</h2>
            <dl class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach ($preview->mappedFields as $field => $header)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                        <dt class="font-medium text-slate-700">{{ $field }}</dt>
                        <dd class="text-slate-500">{{ $header ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
            @if (! empty($preview->unknownColumns))
                <p class="mt-3 text-sm text-amber-700">
                    {{ __('Unknown columns:') }} {{ implode(', ', $preview->unknownColumns) }}
                </p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-3">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Row preview') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Row') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Values') }}</th>
                            <th class="px-4 py-2 text-left font-medium text-slate-500">{{ __('Errors') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (array_slice($preview->rows, 0, 50) as $row)
                            <tr>
                                <td class="px-4 py-2 text-slate-700">{{ $row['row_number'] }}</td>
                                <td class="px-4 py-2">
                                    @if ($row['valid'])
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">{{ __('Valid') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800">{{ __('Invalid') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-slate-600">
                                    {{ collect($row['values'])->filter()->map(fn ($v, $k) => $k.': '.$v)->take(4)->implode(' · ') }}
                                </td>
                                <td class="px-4 py-2 text-rose-700">
                                    {{ implode('; ', $row['errors']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (count($preview->rows) > 50)
                <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                    {{ __('Showing the first 50 rows. Download the error report for the full list.') }}
                </p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if ($preview->validRows > 0 && $session->status === \App\Models\ImportSession::STATUS_READY)
                <form method="POST" action="{{ route('leads.import.execute', $session) }}">
                    @csrf
                    <x-primary-button>
                        {{ __('Import :count valid rows', ['count' => $preview->validRows]) }}
                    </x-primary-button>
                </form>
            @endif

            @if (! empty($preview->errors))
                <a href="{{ route('leads.import.report', $session) }}" class="inline-flex items-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                    {{ __('Download Validation Report') }}
                </a>
                <a href="{{ route('leads.import.report.xlsx', $session) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    {{ __('Download Validation Report (Excel)') }}
                </a>
            @endif

            @if ($preview->invalidRows > 0 || ! empty($preview->errors))
                <a href="{{ route('leads.import.errors', $session) }}" class="text-sm text-slate-600 hover:text-slate-800">
                    {{ __('Download error report') }}
                </a>
            @endif

            <a href="{{ route('leads.index') }}" class="text-sm text-slate-600 hover:text-slate-800">{{ __('Cancel') }}</a>
        </div>
    </div>
</x-app-layout>
