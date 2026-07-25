@php
    $statusVariant = match ($summary['status'] ?? 'partial') {
        'healthy' => 'success',
        'critical' => 'danger',
        default => 'warning',
    };
@endphp

<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Integration Providers')"
        :subtitle="__('Credential health for external services')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Providers'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="mb-4 grid gap-4 sm:grid-cols-3">
            <x-ui.stat-card :label="__('Configured')" :value="($summary['healthy'] ?? 0) . ' / ' . ($summary['total'] ?? 0)" />
            <x-ui.card class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-ink-muted">{{ __('Overall Status') }}</p>
                    <div class="mt-2">
                        <x-ui.badge :variant="$statusVariant">{{ ucfirst($summary['status'] ?? 'unknown') }}</x-ui.badge>
                    </div>
                </div>
            </x-ui.card>
        </div>

        @if (empty($summary['items']))
            <x-ui.card><x-ui.empty-state-preset variant="providers" /></x-ui.card>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($summary['items'] as $provider)
                    @php
                        $providerVariant = match ($provider['status']) {
                            'configured' => 'success',
                            'partial' => 'warning',
                            'missing' => 'danger',
                            default => 'neutral',
                        };
                    @endphp
                    <x-ui.card>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-ink-heading">{{ $provider['label'] }}</h2>
                                <p class="mt-1 text-xs text-ink-muted">{{ ucfirst($provider['category'] ?? 'other') }}</p>
                            </div>
                            <x-ui.badge :variant="$providerVariant">{{ ucfirst($provider['status']) }}</x-ui.badge>
                        </div>
                        <p class="mt-3 text-sm text-ink-muted">
                            {{ __(':configured of :total environment keys configured', [
                                'configured' => $provider['configured_keys'],
                                'total' => $provider['total_keys'],
                            ]) }}
                        </p>
                        <div class="mt-4">
                            <x-ui.button :href="route('platform.providers.show', $provider['key'])" variant="secondary" size="sm">
                                {{ __('Inspect') }}
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
