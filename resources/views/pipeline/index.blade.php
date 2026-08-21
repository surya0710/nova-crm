@php
    $stageVariant = [
        'qualification' => 'info',
        'proposal' => 'primary',
        'negotiation' => 'warning',
        'closed_won' => 'success',
        'closed_lost' => 'neutral',
    ];
    $openStages = config('pipeline.open_stages', []);
    $density = $shellNav['density'] ?? 'comfortable';
    $currency = $organization->currency ?? 'USD';
    $queryBase = request()->except(['page']);
    $boardHref = route('pipeline.index', array_merge($queryBase, ['view' => 'board']));
    $listHref = route('pipeline.index', array_merge($queryBase, ['view' => 'list']));
    $columns = [
        __('Deal'),
        __('Stage'),
        ['label' => __('Value'), 'align' => 'right'],
        ['label' => __('Customer'), 'class' => 'hidden md:table-cell'],
        ['label' => __('Close Date'), 'class' => 'hidden lg:table-cell'],
    ];
    $boardEmpty = $viewMode === 'board' && collect($boardOpportunities)->flatten()->isEmpty();
    $listEmpty = $viewMode === 'list' && $opportunities->isEmpty();
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-listing
        :title="crm_term('pipeline')"
        :subtitle="__('Track deals through your sales pipeline')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('pipeline'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <div class="flex items-center gap-1 rounded-md border border-line bg-surface-muted p-0.5">
                <x-ui.button
                    :href="$boardHref"
                    :variant="$viewMode === 'board' ? 'primary' : 'ghost'"
                    size="sm"
                >{{ __('Board') }}</x-ui.button>
                <x-ui.button
                    :href="$listHref"
                    :variant="$viewMode === 'list' ? 'primary' : 'ghost'"
                    size="sm"
                >{{ __('List') }}</x-ui.button>
            </div>
            @can('create', App\Models\Opportunity::class)
                <x-ui.button :href="route('pipeline.create')" variant="primary" size="sm">{{ __('Add Deal') }}</x-ui.button>
            @endcan
        </x-slot:actions>

        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-ui.stat-card
                :label="__('Open Deals')"
                :value="(string) $pipelineSummary['open_count']"
                :hint="__('Active in pipeline')"
            />
            <x-ui.stat-card
                :label="__('Open Pipeline Value')"
                :value="number_format($pipelineSummary['open_value'], 0).' '.$currency"
                :hint="__('Excludes closed deals')"
            />
            <x-ui.stat-card
                :label="__('Weighted pipeline')"
                :value="number_format($pipelineSummary['weighted_value'] ?? 0, 0).' '.$currency"
                :hint="__('Open value × probability')"
            />
            <a href="{{ route('pipeline.index', array_merge($queryBase, ['view' => 'list', 'stage' => 'closed_won'])) }}" class="block transition hover:opacity-90">
                <x-ui.stat-card
                    :label="__('Won')"
                    :value="(string) $pipelineSummary['won_count']"
                    :hint="__('Closed won')"
                />
            </a>
            <a href="{{ route('pipeline.index', array_merge($queryBase, ['view' => 'list', 'stage' => 'closed_lost'])) }}" class="block transition hover:opacity-90">
                <x-ui.stat-card
                    :label="__('Lost')"
                    :value="(string) $pipelineSummary['lost_count']"
                    :hint="__('Closed lost')"
                />
            </a>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            @foreach (config('pipeline.stages') as $value => $label)
                <a
                    href="{{ route('pipeline.index', array_merge($queryBase, ['view' => $viewMode, 'stage' => $value])) }}"
                    @class([
                        'inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-xs font-medium transition',
                        'border-primary-300 bg-primary-50 text-primary-800 ring-1 ring-primary-200' => ($filters['stage'] ?? '') === $value,
                        'border-line bg-surface-card text-ink-muted hover:border-primary-200 hover:text-ink-heading' => ($filters['stage'] ?? '') !== $value,
                    ])
                >
                    <span>{{ $label }}</span>
                    <x-ui.badge :variant="$stageVariant[$value] ?? 'neutral'">{{ $stageCounts[$value] ?? 0 }}</x-ui.badge>
                </a>
            @endforeach
            @if (! empty($filters['stage']))
                <a
                    href="{{ route('pipeline.index', array_merge(collect($queryBase)->except('stage')->all(), ['view' => $viewMode])) }}"
                    class="inline-flex items-center rounded-md px-2 py-1.5 text-xs font-medium text-ink-muted hover:text-ink-heading"
                >{{ __('Clear stage') }}</a>
            @endif
        </div>

        <x-slot:filters>
            <form method="GET" action="{{ route('pipeline.index') }}" id="pipeline-index-filters" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input type="hidden" name="view" value="{{ $viewMode }}">
                <div class="lg:col-span-2">
                    <label for="pipeline-search" class="sr-only">{{ __('Search deals') }}</label>
                    <x-forms.input id="pipeline-search" name="search" :value="$filters['search'] ?? ''" placeholder="{{ __('Search deals or customers…') }}" />
                </div>
                <x-forms.select name="stage" aria-label="{{ __('Stage') }}">
                    <option value="">{{ __('All stages') }}</option>
                    @foreach (config('pipeline.stages') as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['stage'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </x-forms.select>
                <x-forms.select name="customer_id" aria-label="{{ __('Customer') }}">
                    <option value="">{{ __('All customers') }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(($filters['customer_id'] ?? '') == $customer->id)>{{ $customer->display_name }}</option>
                    @endforeach
                </x-forms.select>
                <div class="flex gap-2">
                    <x-forms.select name="assigned_to" class="flex-1" aria-label="{{ __('Assignee') }}">
                        <option value="">{{ __('Anyone') }}</option>
                        @foreach ($assignees as $member)
                            <option value="{{ $member->id }}" @selected(($filters['assigned_to'] ?? '') == $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-ui.button type="submit" variant="primary" size="sm">{{ __('Filter') }}</x-ui.button>
                </div>
                @include('metadata-fields._index_query_controls')
            </form>
            <div class="mt-3">
                @include('metadata-fields._saved_filter_controls', ['filterFormId' => 'pipeline-index-filters'])
            </div>
        </x-slot:filters>

        @if ($viewMode === 'board')
            @if ($boardEmpty)
                <x-ui.card>
                    @if (! empty($filters['search']) || ! empty($filters['customer_id']) || ! empty($filters['assigned_to']))
                        <x-ui.empty-state-preset variant="search" :title="__('No deals match')" :description="__('Try different filters or clear the search.')" />
                    @else
                        <x-ui.empty-state-preset
                            variant="generic"
                            :title="__('No deals in the pipeline yet')"
                            :description="__('Add a deal to start tracking opportunities through your stages.')"
                            :action-href="auth()->user()->can('create', App\Models\Opportunity::class) ? route('pipeline.create') : null"
                            :action-label="__('Add Deal')"
                        />
                    @endif
                </x-ui.card>
            @else
                <form id="pipeline-stage-drop-form" method="POST" class="hidden" aria-hidden="true">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="stage" id="pipeline-drop-stage" value="">
                </form>

                <div id="pipeline-board" class="flex gap-4 overflow-x-auto pb-2">
                    @foreach (config('pipeline.stages') as $stage => $stageLabel)
                        @php
                            $isOpenStage = in_array($stage, $openStages, true);
                            $columnDeals = $boardOpportunities->get($stage, collect());
                        @endphp
                        <div
                            @class([
                                'flex w-72 min-w-[17rem] shrink-0 flex-col rounded-xl border border-line bg-surface-muted/40',
                                'pipeline-drop-column' => $isOpenStage,
                            ])
                            @if ($isOpenStage)
                                data-drop-stage="{{ $stage }}"
                            @endif
                        >
                            <div class="flex items-center gap-2 border-b border-line px-3 py-2.5">
                                <h2 class="text-sm font-semibold text-ink-heading">{{ $stageLabel }}</h2>
                                <x-ui.badge :variant="$stageVariant[$stage] ?? 'neutral'">{{ $columnDeals->count() }}</x-ui.badge>
                                @unless ($isOpenStage)
                                    <span class="ms-auto text-[10px] font-medium uppercase tracking-wide text-ink-muted">{{ __('View only') }}</span>
                                @endunless
                            </div>
                            <div class="max-h-[70vh] space-y-2 overflow-y-auto p-2">
                                @forelse ($columnDeals as $opportunity)
                                    @php $canDrag = $isOpenStage && auth()->user()->can('update', $opportunity); @endphp
                                    <div
                                        @class([
                                            'rounded-lg border border-line bg-surface-card p-3 shadow-sm transition hover:border-primary-300',
                                            'cursor-grab active:cursor-grabbing' => $canDrag,
                                        ])
                                        @if ($canDrag)
                                            draggable="true"
                                            data-stage-url="{{ route('pipeline.stage.update', $opportunity) }}"
                                            data-stage="{{ $opportunity->stage }}"
                                        @endif
                                    >
                                        <a href="{{ route('pipeline.show', $opportunity) }}" class="block">
                                            <p class="text-sm font-semibold text-ink-heading hover:text-primary-700">{{ $opportunity->title }}</p>
                                            <p class="mt-0.5 text-xs text-ink-muted">{{ $opportunity->customer?->display_name ?? __('No customer') }}</p>
                                            <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                                                <span class="font-medium text-ink-heading">
                                                    @if ($opportunity->amount)
                                                        {{ $opportunity->currency }} {{ number_format($opportunity->amount, 0) }}
                                                    @else
                                                        —
                                                    @endif
                                                </span>
                                                <span class="text-ink-muted">
                                                    {{ $opportunity->probability !== null ? $opportunity->probability.'%' : '—' }}
                                                </span>
                                            </div>
                                        </a>
                                    </div>
                                @empty
                                    <p class="px-2 py-6 text-center text-xs text-ink-muted">{{ __('No deals') }}</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>

                <script>
                    (function () {
                        const board = document.getElementById('pipeline-board');
                        const form = document.getElementById('pipeline-stage-drop-form');
                        const stageInput = document.getElementById('pipeline-drop-stage');
                        if (!board || !form || !stageInput) return;

                        board.querySelectorAll('[draggable="true"]').forEach((card) => {
                            card.addEventListener('dragstart', (event) => {
                                event.dataTransfer.setData('application/x-opportunity-url', card.dataset.stageUrl);
                                event.dataTransfer.setData('application/x-opportunity-stage', card.dataset.stage);
                                event.dataTransfer.effectAllowed = 'move';
                            });
                        });

                        board.querySelectorAll('.pipeline-drop-column').forEach((column) => {
                            column.addEventListener('dragover', (event) => {
                                event.preventDefault();
                                column.classList.add('ring-2', 'ring-primary-300');
                            });
                            column.addEventListener('dragleave', () => {
                                column.classList.remove('ring-2', 'ring-primary-300');
                            });
                            column.addEventListener('drop', (event) => {
                                event.preventDefault();
                                column.classList.remove('ring-2', 'ring-primary-300');
                                const url = event.dataTransfer.getData('application/x-opportunity-url');
                                const fromStage = event.dataTransfer.getData('application/x-opportunity-stage');
                                const stage = column.dataset.dropStage;
                                if (!url || !stage || fromStage === stage) return;
                                form.action = url;
                                stageInput.value = stage;
                                form.submit();
                            });
                        });
                    })();
                </script>
            @endif
        @else
            @if ($listEmpty)
                <x-ui.card>
                    @if (! empty($filters['search']) || ! empty($filters['stage']) || ! empty($filters['customer_id']) || ! empty($filters['assigned_to']))
                        <x-ui.empty-state-preset variant="search" :title="__('No deals match')" :description="__('Try different filters or clear the search.')" />
                    @else
                        <x-ui.empty-state-preset
                            variant="generic"
                            :title="__('No deals in the pipeline yet')"
                            :description="__('Add a deal to start tracking opportunities through your stages.')"
                            :action-href="auth()->user()->can('create', App\Models\Opportunity::class) ? route('pipeline.create') : null"
                            :action-label="__('Add Deal')"
                        />
                    @endif
                </x-ui.card>
            @else
                <x-tables.table :columns="$columns" :dense="$density === 'compact'" sticky>
                    @foreach ($opportunities as $opportunity)
                        <tr class="transition hover:bg-surface-muted/60">
                            <td class="px-4 py-3">
                                <a href="{{ route('pipeline.show', $opportunity) }}" class="group block">
                                    <p class="text-sm font-semibold text-ink-heading group-hover:text-primary-700">{{ $opportunity->title }}</p>
                                    <p class="mt-0.5 text-xs text-ink-muted">{{ $opportunity->assignee?->name ?? __('Unassigned') }}</p>
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$stageVariant[$opportunity->stage] ?? 'neutral'">{{ $opportunity->stage_label }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-ink-heading">
                                @if ($opportunity->amount)
                                    {{ $opportunity->currency }} {{ number_format($opportunity->amount, 0) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="hidden px-4 py-3 text-sm text-ink-muted md:table-cell">{{ $opportunity->customer?->display_name ?? '—' }}</td>
                            <td class="hidden px-4 py-3 text-sm text-ink-muted lg:table-cell">{{ $opportunity->expected_close_date?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-tables.table>
            @endif
        @endif

        @if ($viewMode === 'list' && $opportunities->hasPages())
            <x-slot:pagination>
                {{ $opportunities->links() }}
            </x-slot:pagination>
        @endif
    </x-layouts.entity-listing>
</x-app-layout>
