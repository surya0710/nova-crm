<x-platform-layout>
    <x-layouts.entity-listing
        :title="__('Subscription Plans')"
        :subtitle="__('Plan catalog and entitlements')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Platform'), 'href' => route('platform.dashboard')],
                ['label' => __('Subscriptions'), 'href' => route('platform.subscriptions.index')],
                ['label' => __('Plans'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-ui.button :href="route('platform.licensing.index')" variant="secondary" size="sm">{{ __('Plan Builder') }}</x-ui.button>
        </x-slot:actions>

        @if (empty($plans))
            <x-ui.card><x-ui.empty-state-preset variant="plans" /></x-ui.card>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($plans as $slug => $plan)
                    <x-ui.card>
                        <h2 class="text-base font-semibold text-ink-heading">{{ $plan['name'] ?? $slug }}</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ $plan['description'] ?? '' }}</p>
                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between gap-3 border-b border-line pb-2">
                                <dt class="text-ink-muted">{{ __('Users') }}</dt>
                                <dd class="font-medium text-ink">{{ $plan['limits']['users'] ?? __('Unlimited') }}</dd>
                            </div>
                            <div class="flex justify-between gap-3 border-b border-line pb-2">
                                <dt class="text-ink-muted">{{ __('Storage') }}</dt>
                                <dd class="font-medium text-ink">{{ isset($plan['limits']['storage_mb']) ? number_format($plan['limits']['storage_mb']) . ' MB' : __('Unlimited') }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-ink-muted">{{ __('Modules') }}</dt>
                                <dd class="text-right text-ink">
                                    @if (($plan['modules'] ?? null) === '*')
                                        {{ __('All modules') }}
                                    @else
                                        {{ count($plan['modules'] ?? []) }}
                                    @endif
                                </dd>
                            </div>
                        </dl>
                        @if (! empty($plan['features']))
                            <div class="mt-4 flex flex-wrap gap-1">
                                @foreach ($plan['features'] as $feature => $enabled)
                                    <x-ui.badge :variant="$enabled ? 'success' : 'neutral'" size="sm">{{ $feature }}</x-ui.badge>
                                @endforeach
                            </div>
                        @endif
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </x-layouts.entity-listing>
</x-platform-layout>
