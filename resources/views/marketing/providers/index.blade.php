@php
    $healthVariant = fn (?string $health) => match ($health) {
        'healthy' => 'success',
        'degraded' => 'warning',
        'unhealthy' => 'danger',
        'disconnected' => 'neutral',
        default => 'neutral',
    };
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Marketing Providers')"
        :subtitle="__('Ad and analytics integrations for campaigns')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Marketing'), 'href' => route('marketing.home')],
                ['label' => __('Providers'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="$integrationsHref" variant="secondary" size="sm">{{ __('Open Integrations') }}</x-ui.button>
        </x-slot:actions>

        @if ($cards->isEmpty())
            <x-ui.card>
                <x-ui.empty-state-preset variant="providers" :action-href="$integrationsHref" />
            </x-ui.card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($cards as $card)
                    <x-ui.card>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-ink-heading truncate">{{ $card['name'] ?? $card['slug'] }}</h3>
                                <p class="mt-1 text-xs text-ink-muted">{{ $card['status_label'] ?? ($card['status'] ?? '—') }}</p>
                            </div>
                            <x-ui.badge :variant="$healthVariant($card['health'] ?? null)">{{ $card['health_label'] ?? __('Unknown') }}</x-ui.badge>
                        </div>
                        @if (! empty($card['last_error']))
                            <p class="mt-3 text-xs text-danger">{{ Str::limit($card['last_error'], 120) }}</p>
                        @endif
                        <div class="mt-4">
                            <x-ui.button :href="$integrationsHref" variant="secondary" size="sm">{{ __('Manage') }}</x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif

        @if (! empty($planned))
            <section class="mt-8" aria-labelledby="planned-providers-heading">
                <h2 id="planned-providers-heading" class="text-sm font-semibold text-ink-heading">{{ __('Planned providers') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('Additional integrations on the roadmap.') }}</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($planned as $provider)
                        <x-ui.card>
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-medium text-ink-heading">{{ $provider['name'] }}</h3>
                                <x-ui.badge variant="neutral">{{ __('Planned') }}</x-ui.badge>
                            </div>
                            <p class="mt-2 text-xs text-ink-muted">{{ __(ucfirst($provider['channel'] ?? 'integration')) }}</p>
                        </x-ui.card>
                    @endforeach
                </div>
            </section>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
