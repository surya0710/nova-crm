@php
    $confidenceVariant = fn (?string $confidence) => match ($confidence) {
        'high' => 'success',
        'medium' => 'warning',
        'low' => 'neutral',
        default => 'neutral',
    };
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('AI Insights')"
        :subtitle="__('Generated observations from organizational data')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Analytics'), 'href' => route('analytics.home')],
                ['label' => __('AI Insights'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-6 rounded-xl border border-warning/30 bg-warning/10 p-4" role="status">
            <div class="flex items-start gap-3">
                <x-ui.badge variant="warning">{{ __('Requires review') }}</x-ui.badge>
                <p class="text-sm text-ink-heading">
                    {{ __('All insights below are generated from organizational data and require human review before acting on them.') }}
                </p>
            </div>
        </div>

        @if (empty($insights))
            <x-ui.card>
                <x-ui.empty-state-preset variant="analytics" :description="__('Insights will appear when sufficient data is available.')" />
            </x-ui.card>
        @else
            <div class="space-y-4">
                @foreach ($insights as $insight)
                    <article class="rounded-xl border border-line bg-surface-card p-5 shadow-sm" aria-labelledby="insight-{{ $loop->index }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge variant="primary">{{ __(ucfirst(str_replace('_', ' ', $insight['category'] ?? 'general'))) }}</x-ui.badge>
                                    <x-ui.badge :variant="$confidenceVariant($insight['confidence'] ?? null)">{{ __(ucfirst($insight['confidence'] ?? 'unknown')) }} {{ __('confidence') }}</x-ui.badge>
                                    @if ($insight['requires_review'] ?? false)
                                        <x-ui.badge variant="warning">{{ __('Requires review') }}</x-ui.badge>
                                    @endif
                                </div>
                                <h2 id="insight-{{ $loop->index }}" class="mt-2 text-base font-semibold text-ink-heading">{{ $insight['title'] ?? __('Insight') }}</h2>
                            </div>
                        </div>

                        <p class="mt-3 text-sm text-ink">{{ $insight['summary'] ?? '' }}</p>

                        @if (! empty($insight['review_note']))
                            <p class="mt-3 rounded-lg border border-line bg-surface-muted/40 px-3 py-2 text-xs text-ink-muted">{{ $insight['review_note'] }}</p>
                        @endif

                        @if (! empty($insight['metrics']) && is_array($insight['metrics']))
                            <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 text-sm">
                                @foreach ($insight['metrics'] as $metricKey => $metricValue)
                                    @if (! is_array($metricValue))
                                        <div>
                                            <dt class="text-xs text-ink-muted">{{ __(ucfirst(str_replace('_', ' ', $metricKey))) }}</dt>
                                            <dd class="mt-1 font-semibold text-ink-heading">
                                                {{ is_numeric($metricValue) ? number_format((float) $metricValue, is_float($metricValue) ? 1 : 0) : $metricValue }}
                                            </dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
