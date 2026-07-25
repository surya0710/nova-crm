<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Documentation Health') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Validation summary, coverage, and integrity checks for Knowledge Center documentation.') }}</p>
        </div>
    </x-slot>

    @php
        $statistics = $report['statistics'];
        $issues = collect($report['issues']);
        $errors = $issues->where('type', 'error');
        $warnings = $issues->where('type', 'warning');
        $status = (string) $report['status'];
        $statusClasses = match ($status) {
            'healthy' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
            default => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
            @include('knowledge.partials.search')
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <aside class="lg:col-span-3">
                @include('knowledge.partials.sidebar', ['navigationTree' => $navigationTree])
            </aside>

            <section class="lg:col-span-9 space-y-6">
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">{{ __('Overall Health') }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ __('Generated at :time', ['time' => $report['generated_at']]) }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset {{ $statusClasses }}">
                            {{ ucfirst($status) }}
                        </span>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-lg border border-slate-200 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Modules') }}</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $statistics['total_modules'] }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Documents') }}</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $statistics['total_documents'] }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Errors') }}</p>
                            <p class="mt-1 text-2xl font-semibold text-rose-600">{{ $statistics['errors'] }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Warnings') }}</p>
                            <p class="mt-1 text-2xl font-semibold text-amber-600">{{ $statistics['warnings'] }}</p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span class="font-medium">{{ __('Missing documents') }}:</span> {{ $statistics['missing_documents'] }}
                        </div>
                        <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span class="font-medium">{{ __('Broken links') }}:</span> {{ $statistics['broken_links'] }}
                        </div>
                        <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span class="font-medium">{{ __('Invalid metadata') }}:</span> {{ $statistics['invalid_metadata'] }}
                        </div>
                        <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <span class="font-medium">{{ __('Invalid anchors') }}:</span> {{ $statistics['invalid_anchors'] }}
                        </div>
                    </div>
                </div>

                <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Module Coverage') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Required documentation sections per enabled module.') }}</p>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Module') }}</th>
                                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Documents') }}</th>
                                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Sections') }}</th>
                                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Errors') }}</th>
                                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Warnings') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($report['coverage'] as $entry)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-900">{{ $entry['module_name'] }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $entry['documents'] }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $entry['present_sections'] }}/{{ $entry['required_sections'] }}</td>
                                        <td class="px-4 py-3 text-rose-600">{{ $entry['errors'] }}</td>
                                        <td class="px-4 py-3 text-amber-600">{{ $entry['warnings'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ __('No module coverage data available.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($errors->isNotEmpty())
                    <div class="rounded-xl bg-white border border-rose-200 shadow-sm p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-rose-700">{{ __('Validation Errors') }}</h2>
                        <ul class="mt-4 space-y-2 text-sm text-slate-700">
                            @foreach ($errors as $issue)
                                <li class="rounded-lg border border-rose-100 bg-rose-50 px-4 py-3">{{ $issue['message'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($warnings->isNotEmpty())
                    <div class="rounded-xl bg-white border border-amber-200 shadow-sm p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-amber-700">{{ __('Validation Warnings') }}</h2>
                        <ul class="mt-4 space-y-2 text-sm text-slate-700">
                            @foreach ($warnings as $issue)
                                <li class="rounded-lg border border-amber-100 bg-amber-50 px-4 py-3">{{ $issue['message'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($errors->isEmpty() && $warnings->isEmpty())
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-emerald-800">{{ __('All Checks Passed') }}</h2>
                        <p class="mt-2 text-sm text-emerald-700">{{ __('Documentation validation completed with no errors or warnings.') }}</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
