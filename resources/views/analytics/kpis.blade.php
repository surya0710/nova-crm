@php
    $statusVariant = fn (?string $status) => match ($status) {
        'critical' => 'danger',
        'warning' => 'warning',
        'ok' => 'success',
        default => 'neutral',
    };
    $formatValue = function ($value, ?string $unit) {
        if ($value === null) {
            return '—';
        }
        if ($unit === 'distribution' && is_array($value)) {
            return collect($value)->map(fn ($count, $label) => "$label: $count")->implode(', ') ?: '—';
        }
        if ($unit === 'percent') {
            return is_numeric($value) ? number_format((float) $value, 1).'%' : $value;
        }
        if ($unit === 'currency') {
            return is_numeric($value) ? number_format((float) $value, 0) : $value;
        }
        if (is_numeric($value)) {
            return number_format((float) $value, is_float($value) ? 1 : 0);
        }

        return $value;
    };
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('KPI Library')"
        :subtitle="__('Catalog of metrics with thresholds and live values')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Analytics'), 'href' => route('analytics.home')],
                ['label' => __('KPI Library'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        @if (empty($catalog))
            <x-ui.card>
                <x-ui.empty-state-preset variant="kpis" />
            </x-ui.card>
        @else
            <div class="space-y-8">
                @foreach ($catalog as $categoryKey => $category)
                    <section aria-labelledby="category-{{ $categoryKey }}">
                        <h2 id="category-{{ $categoryKey }}" class="text-lg font-semibold text-ink-heading">{{ __($category['label'] ?? ucfirst($categoryKey)) }}</h2>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($category['kpis'] ?? [] as $kpiKey => $kpi)
                                <x-ui.card id="{{ $categoryKey }}-{{ $kpiKey }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-ink-heading">{{ __($kpi['label'] ?? ucfirst(str_replace('_', ' ', $kpiKey))) }}</h3>
                                        @if ($kpi['status'] ?? null)
                                            <x-ui.badge :variant="$statusVariant($kpi['status'])">{{ __(ucfirst($kpi['status'])) }}</x-ui.badge>
                                        @endif
                                    </div>

                                    @if (! empty($kpi['description']))
                                        <p class="mt-1 text-xs text-ink-muted">{{ __($kpi['description']) }}</p>
                                    @endif

                                    <p class="mt-3 text-2xl font-semibold text-ink-heading">
                                        {{ $formatValue($kpi['resolved_value'] ?? null, $kpi['unit'] ?? null) }}
                                    </p>

                                    @if (! empty($kpi['thresholds']))
                                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                            @if (array_key_exists('warning', $kpi['thresholds']) && $kpi['thresholds']['warning'] !== null)
                                                <div>
                                                    <dt class="text-ink-muted">{{ __('Warning') }}</dt>
                                                    <dd class="font-medium text-ink-heading">{{ $kpi['thresholds']['warning'] }}</dd>
                                                </div>
                                            @endif
                                            @if (array_key_exists('critical', $kpi['thresholds']) && $kpi['thresholds']['critical'] !== null)
                                                <div>
                                                    <dt class="text-ink-muted">{{ __('Critical') }}</dt>
                                                    <dd class="font-medium text-ink-heading">{{ $kpi['thresholds']['critical'] }}</dd>
                                                </div>
                                            @endif
                                        </dl>
                                    @endif
                                </x-ui.card>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
