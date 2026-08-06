<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Developer')"
        :subtitle="__('API tokens, webhooks, rate limits, and OAuth notes')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => route('administration.home')],
                ['label' => __('Developer'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($cards as $card)
                <x-ui.card>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-ink-heading">{{ $card['title'] }}</h3>
                            <p class="mt-1 text-sm text-ink-muted">{{ $card['description'] }}</p>
                        </div>
                        @if (! empty($card['badge']))
                            <x-ui.badge variant="neutral">{{ $card['badge'] }}</x-ui.badge>
                        @endif
                    </div>
                    @if (! empty($card['href']))
                        <div class="mt-4">
                            <x-ui.button :href="$card['href']" variant="secondary" size="sm">{{ __('Open') }}</x-ui.button>
                        </div>
                    @endif
                </x-ui.card>
            @empty
                <div class="md:col-span-2">
                    <x-ui.empty-state-preset variant="api_tokens" />
                </div>
            @endforelse
        </div>

        <x-entity.section :title="__('Rate limits')" class="mt-6">
            <ul class="divide-y divide-line">
                @foreach ($rateLimits as $limit)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="font-medium text-ink-heading">{{ $limit['label'] }}</span>
                        <span class="text-ink-muted">{{ $limit['value'] }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs text-ink-muted">
                {{ __('Personal access tokens use Laravel Sanctum. OAuth provider callbacks are registered under marketing.providers.* routes.') }}
            </p>
        </x-entity.section>
    </x-layouts.settings>
</x-app-layout>
