<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="__('Reports Center')"
        :subtitle="__('Compiled metrics and report shortcuts')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('Analytics'), 'href' => route('analytics.home')],
                ['label' => __('Reports'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <section class="mb-8" aria-labelledby="compiled-summary-heading">
            <h2 id="compiled-summary-heading" class="text-sm font-semibold text-ink-heading">{{ __('Compiled summary') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.stat-card
                    :label="__('Revenue collected')"
                    :value="number_format((float) ($compiled['revenue_collected'] ?? 0), 0)"
                    :hint="($compiled['currency'] ?? null) ? __('Currency: :code', ['code' => $compiled['currency']]) : null"
                />
                <x-ui.stat-card
                    :label="__('Outstanding AR')"
                    :value="number_format((float) ($compiled['outstanding_amount'] ?? 0), 0)"
                    :hint="__(':count invoices', ['count' => number_format((int) ($compiled['outstanding_count'] ?? 0))])"
                />
                <x-ui.stat-card
                    :label="__('Open pipeline')"
                    :value="number_format((float) ($compiled['open_pipeline_value'] ?? 0), 0)"
                />
                <x-ui.stat-card
                    :label="__('Win rate')"
                    :value="isset($compiled['conversion_rate']) ? $compiled['conversion_rate'].'%' : '—'"
                />
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-workspace.widget :title="__('Sales reports')" :href="$links['index'] ?? null">
                <p class="text-sm text-ink-muted">{{ __('Lead counts, pipeline stages, and top performers.') }}</p>
                @if ($links['index'] ?? null)
                    <div class="mt-4">
                        <x-ui.button :href="$links['index']" variant="secondary" size="sm">{{ __('Open sales reports') }}</x-ui.button>
                    </div>
                @endif
            </x-workspace.widget>

            <x-workspace.widget :title="__('Finance reports')" :href="$links['finance'] ?? null">
                <p class="text-sm text-ink-muted">{{ __('Outstanding receivables, revenue, and payment methods.') }}</p>
                @if ($links['finance'] ?? null)
                    <div class="mt-4">
                        <x-ui.button :href="$links['finance']" variant="secondary" size="sm">{{ __('Open finance') }}</x-ui.button>
                    </div>
                @endif
            </x-workspace.widget>

            <x-workspace.widget :title="__('Project reports')" :href="$links['projects'] ?? null">
                <p class="text-sm text-ink-muted">{{ __('Delivery and portfolio reporting hub.') }}</p>
                @if ($links['projects'] ?? null)
                    <div class="mt-4">
                        <x-ui.button :href="$links['projects']" variant="secondary" size="sm">{{ __('Open project reports') }}</x-ui.button>
                    </div>
                @endif
            </x-workspace.widget>

            <x-workspace.widget :title="__('Recruitment reports')" :href="$links['recruitment'] ?? null">
                <p class="text-sm text-ink-muted">{{ __('Hiring funnel and recruitment analytics.') }}</p>
                @if ($links['recruitment'] ?? null)
                    <div class="mt-4">
                        <x-ui.button :href="$links['recruitment']" variant="secondary" size="sm">{{ __('Open recruitment analytics') }}</x-ui.button>
                    </div>
                @endif
            </x-workspace.widget>
        </div>

        <section class="mt-8" aria-labelledby="report-sections-heading">
            <h2 id="report-sections-heading" class="text-sm font-semibold text-ink-heading">{{ __('Report sections') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-heading">{{ __('Scheduled reports') }}</h3>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('Automated report delivery when scheduling is configured.') }}</p>
                    @if (\Illuminate\Support\Facades\Route::has('reports.index'))
                        <div class="mt-4">
                            <x-ui.button :href="route('reports.index')" variant="link" size="sm">{{ __('View reports') }}</x-ui.button>
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-heading">{{ __('Saved reports') }}</h3>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('Persisted report configurations and filters.') }}</p>
                    @if (\Illuminate\Support\Facades\Route::has('hrms.recruitment.saved-reports.index'))
                        <div class="mt-4">
                            <x-ui.button :href="route('hrms.recruitment.saved-reports.index')" variant="link" size="sm">{{ __('Recruitment saved reports') }}</x-ui.button>
                        </div>
                    @elseif ($links['index'] ?? null)
                        <div class="mt-4">
                            <x-ui.button :href="$links['index']" variant="link" size="sm">{{ __('View reports') }}</x-ui.button>
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-heading">{{ __('Templates') }}</h3>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('Dashboard templates for common analytics views.') }}</p>
                    @if (\Illuminate\Support\Facades\Route::has('analytics.dashboards.index'))
                        <div class="mt-4">
                            <x-ui.button :href="route('analytics.dashboards.index')" variant="link" size="sm">{{ __('Browse templates') }}</x-ui.button>
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-heading">{{ __('Export') }}</h3>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('Download finance and revenue exports.') }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if (\Illuminate\Support\Facades\Route::has('reports.export.outstanding'))
                            <x-ui.button :href="route('reports.export.outstanding')" variant="secondary" size="sm">{{ __('Outstanding') }}</x-ui.button>
                        @endif
                        @if (\Illuminate\Support\Facades\Route::has('reports.export.revenue'))
                            <x-ui.button :href="route('reports.export.revenue')" variant="secondary" size="sm">{{ __('Revenue') }}</x-ui.button>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink-heading">{{ __('Sharing') }}</h3>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('Share saved reports with team members.') }}</p>
                    @if (\Illuminate\Support\Facades\Route::has('hrms.recruitment.saved-reports.index'))
                        <div class="mt-4">
                            <x-ui.button :href="route('hrms.recruitment.saved-reports.index')" variant="link" size="sm">{{ __('Manage shared reports') }}</x-ui.button>
                        </div>
                    @endif
                </x-ui.card>
            </div>
        </section>

        @if (($compiled['opportunity_by_stage'] ?? collect())->isNotEmpty())
            <x-workspace.widget :title="__('Pipeline by stage')" class="mt-8">
                <ul class="space-y-2 text-sm">
                    @foreach ($compiled['opportunity_by_stage'] as $stage => $row)
                        <li class="flex justify-between gap-2">
                            <span class="text-ink-heading">{{ config('pipeline.stages.'.$stage, $stage) }}</span>
                            <span class="text-ink-muted">{{ $row->count ?? 0 }} · {{ number_format((float) ($row->value ?? 0), 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-workspace.widget>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
