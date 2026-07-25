<x-app-layout>
    <x-flash-messages />

    <x-layouts.settings
        :title="__('Organization Settings')"
        :subtitle="$organization->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Administration'), 'href' => \Illuminate\Support\Facades\Route::has('administration.home') ? route('administration.home') : null],
                ['label' => __('Configuration Hub'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <div class="space-y-8">
            @foreach ($groups as $groupKey => $groupLabel)
                @php $items = $groupedSections->get($groupKey, collect()); @endphp
                @continue($items->isEmpty())
                <section>
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-ink-muted">{{ __($groupLabel) }}</h2>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($items as $key => $section)
                            @php
                                $routeName = $section['route'] ?? null;
                                $href = $routeName && \Illuminate\Support\Facades\Route::has($routeName)
                                    ? route($routeName, $section['query'] ?? [])
                                    : '#';
                            @endphp
                            <a href="{{ $href }}" class="block rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-200 hover:shadow">
                                <p class="font-medium text-ink-heading">{{ __($section['label']) }}</p>
                                <p class="mt-1 text-xs text-ink-muted">{{ __('Configure :label', ['label' => __($section['label'])]) }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach

            @if (! empty($futureModules))
                <section>
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-ink-muted">{{ __('Future Modules') }}</h2>
                    <x-ui.card>
                        @foreach ($futureModules as $module)
                            <p class="font-medium text-ink-heading">{{ __($module['label']) }} — {{ __('Coming later') }}</p>
                            <p class="mt-1 text-sm text-ink-muted">{{ __($module['reason'] ?? '') }}</p>
                        @endforeach
                    </x-ui.card>
                </section>
            @endif
        </div>
    </x-layouts.settings>
</x-app-layout>
