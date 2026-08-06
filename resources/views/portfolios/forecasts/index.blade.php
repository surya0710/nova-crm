<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing :title="__('Forecasts')">
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Projects'), 'href' => route('projects.home')],
                ['label' => __('Forecasts'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>


    @if ($portfolios->isEmpty())
        <div class="rounded-xl bg-white border border-slate-200 shadow-sm p-12 text-center text-sm text-slate-500">
            {{ __('No active portfolios available for forecasting.') }}
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($portfolios as $portfolio)
                <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full shrink-0" style="background-color: {{ $portfolio->color ?? '#4f46e5' }}"></span>
                            <h2 class="text-sm font-semibold text-slate-900">{{ $portfolio->name }}</h2>
                        </div>
                        @if ($portfolio->code)
                            <p class="mt-1 text-xs text-slate-500">{{ $portfolio->code }}</p>
                        @endif
                    </div>
                    <div class="p-5 flex-1">
                        <dl class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <dt class="text-slate-500">{{ __('Projects') }}</dt>
                                <dd class="text-sm font-medium text-slate-800">{{ $portfolio->projects_count ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">{{ __('Status') }}</dt>
                                <dd class="text-sm font-medium text-slate-800">{{ config('projects.portfolio_statuses')[$portfolio->status] ?? $portfolio->status }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="px-5 py-3 border-t border-slate-100">
                        <a href="{{ route('portfolios.forecasts.show', $portfolio) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">{{ __('View Forecast') }}</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    </x-layouts.entity-listing>
</x-app-layout>
