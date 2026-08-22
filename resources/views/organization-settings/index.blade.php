<x-app-layout>
    <x-flash-messages />

    @php
        $icons = [
            'building' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
            'users' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'receipt' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>',
            'hr' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
            'task' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>',
            'chart' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
            'shield' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
            'cog' => '<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
        ];
        $moduleCount = count($modules);
        $sectionCount = collect($modules)->sum(fn ($module) => count($module['sections'] ?? []));
    @endphp

    <x-layouts.settings
        :title="__('Configuration Hub')"
        :subtitle="$organization->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="$hubBreadcrumbs" />
        </x-slot:breadcrumbs>

        <script type="application/json" id="configuration-hub-data">{!! json_encode([
            'modules' => $modules,
            'recent' => $recentSettings ?? [],
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
        <div
            class="space-y-8"
            x-data="{
                query: '',
                modules: [],
                recent: [],
                init() {
                    try {
                        const payload = JSON.parse(document.getElementById('configuration-hub-data')?.textContent || '{}');
                        this.modules = Array.isArray(payload.modules) ? payload.modules : [];
                        this.recent = Array.isArray(payload.recent) ? payload.recent : [];
                    } catch (e) {
                        this.modules = [];
                        this.recent = [];
                    }
                },
                norm(value) { return (value || '').toString().toLowerCase(); },
                haystack(item) {
                    return this.norm([item?.key, item?.label, item?.description, (item?.keywords || []).join(' ')].join(' '));
                },
                matches(item) {
                    const tokens = this.norm(this.query).split(/\s+/).filter(Boolean);
                    if (! tokens.length) return true;
                    return tokens.every((token) => this.haystack(item).includes(token));
                },
                moduleVisible(module) {
                    if (! module) return true;
                    const sections = module.sections || [];
                    return sections.length === 0 || sections.some((section) => this.matches(section));
                },
                get hasQuery() { return this.norm(this.query).length > 0; },
                get anyVisible() {
                    return (this.modules || []).some((module) => this.moduleVisible(module));
                },
                recentVisible() {
                    if (this.hasQuery) return false;
                    return (this.recent || []).length > 0;
                },
            }"
        >
            <div class="rounded-2xl border border-line bg-surface-card p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">{{ __('Organization settings') }}</p>
                        <p class="mt-1 text-sm text-ink">{{ __('Browse licensed modules and open the existing settings page for each area. This hub does not store settings.') }}</p>
                        <p class="mt-2 text-xs text-ink-muted">{{ trans_choice(':count module|:count modules', $moduleCount, ['count' => $moduleCount]) }} · {{ trans_choice(':count setting|:count settings', $sectionCount, ['count' => $sectionCount]) }}</p>
                    </div>
                    <div class="w-full lg:max-w-sm">
                        <label for="configuration-hub-search" class="sr-only">{{ __('Search settings') }}</label>
                        <x-forms.input
                            id="configuration-hub-search"
                            type="search"
                            x-model="query"
                            :placeholder="__('Search settings, modules, or keywords…')"
                            autocomplete="off"
                        />
                    </div>
                </div>

                @if ($moduleCount > 1)
                    <nav class="mt-4 -mx-1 flex gap-2 overflow-x-auto pb-1 lg:flex-wrap" aria-label="{{ __('Configuration modules') }}">
                        @foreach ($modules as $module)
                            <a
                                href="#module-{{ $module['key'] }}"
                                class="shrink-0 rounded-full border border-line bg-surface px-3 py-1.5 text-xs font-medium text-ink hover:border-primary-200 hover:text-primary-700"
                                x-show="!hasQuery || moduleVisible(modules[{{ $loop->index }}])"
                            >{{ trans_string($module['name']) }}</a>
                        @endforeach
                    </nav>
                @endif
            </div>

            <section x-show="recentVisible()" x-cloak>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-ink-heading">{{ __('Recently used') }}</h2>
                    <p class="text-xs text-ink-muted">{{ __('From this organization, for you') }}</p>
                </div>
                <div class="flex gap-3 overflow-x-auto pb-1 sm:grid sm:grid-cols-2 sm:overflow-visible xl:grid-cols-4">
                    <template x-for="item in recent" :key="item.key">
                        <a :href="item.href" class="min-w-[16rem] shrink-0 rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-200 hover:shadow sm:min-w-0">
                            <p class="text-xs text-ink-muted" x-text="item.module_name"></p>
                            <p class="mt-1 font-medium text-ink-heading" x-text="item.label"></p>
                        </a>
                    </template>
                </div>
            </section>

            @forelse ($modules as $index => $module)
                <section
                    id="module-{{ $module['key'] }}"
                    class="scroll-mt-24"
                    x-show="!hasQuery || moduleVisible(modules[{{ $index }}])"
                >
                    <div class="mb-4 flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-700">{!! $icons[$module['icon']] ?? $icons['cog'] !!}</span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold text-ink-heading">{{ trans_string($module['name']) }}</h2>
                                <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[11px] font-medium text-ink-muted">{{ trans_choice(':count setting|:count settings', count($module['sections']), ['count' => count($module['sections'])]) }}</span>
                            </div>
                            <p class="mt-1 text-sm text-ink-muted">{{ trans_string($module['description']) }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($module['sections'] as $sectionIndex => $section)
                            <a
                                href="{{ $section['href'] }}"
                                class="group flex min-h-[5.5rem] flex-col rounded-xl border border-line bg-surface-card p-4 shadow-sm transition hover:border-primary-200 hover:shadow"
                                x-show="!hasQuery || matches(modules[{{ $index }}]?.sections?.[{{ $sectionIndex }}])"
                            >
                                <p class="font-medium text-ink-heading group-hover:text-primary-700">{{ trans_string($section['label']) }}</p>
                                <p class="mt-1 text-xs leading-5 text-ink-muted">{{ trans_string($section['description']) }}</p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @empty
                <x-ui.empty-state-preset
                    variant="settings"
                    :title="__('No configuration available')"
                    :description="__('There are no settings for the modules enabled on this organization. Disabled or unlicensed modules never appear here.')"
                />
            @endforelse

            <div x-show="hasQuery && ! anyVisible" x-cloak>
                <x-ui.empty-state-preset
                    variant="search"
                    :title="__('No matching settings')"
                    :description="__('Try a module name, setting label, or keyword such as GST, leave, or pipeline.')"
                />
            </div>

            @if ($futureModules->isNotEmpty())
                <section x-show="! hasQuery">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-ink-muted">{{ __('Coming later') }}</h2>
                    <x-ui.card>
                        @forelse ($futureModules as $module)
                            <div @class(['mt-4 pt-4 border-t border-line' => ! $loop->first])>
                                <p class="font-medium text-ink-heading">{{ trans_string($module['label']) }}</p>
                                <p class="mt-1 text-sm text-ink-muted">{{ trans_string($module['reason'] ?? '') }}</p>
                            </div>
                        @empty
                            <x-ui.empty-state-preset
                                variant="settings"
                                :title="__('No upcoming modules listed')"
                                :description="__('Future catalog entries appear here until they are production-ready.')"
                            />
                        @endforelse
                    </x-ui.card>
                </section>
            @endif
        </div>
    </x-layouts.settings>
</x-app-layout>
