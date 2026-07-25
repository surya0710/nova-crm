<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Dashboard Templates')"
        :subtitle="__('Pre-built analytics views by domain')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Analytics'), 'href' => route('analytics.home')],
                ['label' => __('Dashboards'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @if ($personalHref)
                <x-ui.button :href="$personalHref" variant="secondary" size="sm">{{ __('Personal home') }}</x-ui.button>
            @endif
        </x-slot:actions>

        @if (empty($templates))
            <x-ui.card>
                <x-ui.empty-state-preset variant="dashboards" :action-href="$personalHref" />
            </x-ui.card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($templates as $template)
                    <x-ui.card>
                        <h3 class="text-sm font-semibold text-ink-heading">{{ $template['label'] }}</h3>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('Open the :label view for deeper analysis.', ['label' => $template['label']]) }}</p>
                        <div class="mt-4">
                            @if ($template['href'])
                                <x-ui.button :href="$template['href']" variant="primary" size="sm">{{ __('Open') }}</x-ui.button>
                            @else
                                <x-ui.badge variant="neutral">{{ __('Unavailable') }}</x-ui.badge>
                            @endif
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif

        @if ($canCustomize && $personalHref)
            <section class="mt-8 rounded-xl border border-line bg-surface-muted/30 p-4" aria-labelledby="customize-dashboard-heading">
                <h2 id="customize-dashboard-heading" class="text-sm font-semibold text-ink-heading">{{ __('Personal dashboard') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('Return to your personal home dashboard to customize widgets.') }}</p>
                <div class="mt-3">
                    <x-ui.button :href="$personalHref" variant="secondary" size="sm">{{ __('Go to personal home') }}</x-ui.button>
                </div>
            </section>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
