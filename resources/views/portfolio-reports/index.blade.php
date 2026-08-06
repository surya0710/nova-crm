<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">{{ __('Portfolio Reports') }}</h1>
                <p class="text-sm text-slate-500">{{ __('Generate and download EPM reports') }}</p>
            </div>
        </div>
    </x-slot>

    <x-flash-messages />

    @can('create', App\Models\PortfolioReport::class)
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-5 mb-6">
            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ __('Generate Report') }}</h3>
            <form method="POST" action="{{ route('portfolio-reports.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                @csrf
                <div>
                    <x-input-label for="report_type" :value="__('Report Type')" />
                    <select id="report_type" name="report_type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                        @foreach ($reportTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('report_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('report_type')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="format" :value="__('Format')" />
                    <select id="format" name="format" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                        @foreach ($reportFormats as $value => $label)
                            <option value="{{ $value }}" @selected(old('format', 'pdf') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('format')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="portfolio_id" :value="__('Portfolio ID')" />
                    <x-text-input id="portfolio_id" name="portfolio_id" type="number" class="block mt-1 w-full" :value="old('portfolio_id')" />
                </div>
                <div>
                    <x-input-label for="program_id" :value="__('Program ID')" />
                    <x-text-input id="program_id" name="program_id" type="number" class="block mt-1 w-full" :value="old('program_id')" />
                </div>
                <div class="flex items-end">
                    <x-primary-button class="w-full justify-center">{{ __('Generate') }}</x-primary-button>
                </div>
            </form>
        </div>
    @endcan

    <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
        @if ($reports->isEmpty())
            <div class="p-12 text-center text-sm text-slate-500">{{ __('No reports generated yet.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Report') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Scope') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Format') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Generated') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($reports as $report)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $reportTypes[$report->report_type] ?? $report->report_type }}</p>
                                    <p class="text-xs text-slate-500">{{ $report->generator?->name ?? '—' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    @if ($report->portfolio)
                                        {{ $report->portfolio->name }}
                                    @elseif ($report->program)
                                        {{ $report->program->name }}
                                    @else
                                        {{ __('Organization') }}
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex text-xs font-medium px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 uppercase">{{ $report->format }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $report->generated_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('download', $report)
                                        @if ($report->storage_path)
                                            <a href="{{ route('portfolio-reports.download', $report) }}" class="text-sm text-indigo-600 hover:text-indigo-800">{{ __('Download') }}</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($reports->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $reports->links() }}</div>
            @endif
        @endif
    </div>
</x-app-layout>
