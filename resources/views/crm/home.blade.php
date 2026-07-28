<x-app-layout>
    <x-flash-messages />

    <x-layouts.workspace-home
        :title="__('CRM Workspace')"
        :subtitle="__('Win and collect revenue today')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM Workspace'), 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            <x-workspace.quick-actions :actions="$quickActions" />
        </x-slot:actions>

        <x-slot:kpis>
            @forelse ($kpis as $kpi)
                <x-ui.stat-card
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :hint="$kpi['hint'] ?? null"
                />
            @empty
                <x-ui.stat-card :label="__('CRM')" :value="__('—')" :hint="__('No metrics available for your role')" />
            @endforelse
        </x-slot:kpis>

        <div class="space-y-6">
            <x-workspace.widget
                :title="__('Today\'s follow-ups')"
                :subtitle="__('Scheduled conversations')"
                :href="auth()->user()->hasPermission('leads.view') ? route('crm.activities') : null"
            >
                @if ($followUps->isEmpty())
                    <x-ui.empty-state-preset variant="activities" class="!py-6" />
                @else
                    <ul class="divide-y divide-line -mx-1">
                        @foreach ($followUps as $lead)
                            <li class="py-2.5">
                                <a href="{{ route('leads.show', $lead) }}" class="group flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-ink-heading group-hover:text-primary-700 truncate">{{ $lead->name }}</p>
                                        <p class="text-xs text-ink-muted truncate">
                                            {{ $lead->company ?: ($lead->assignee?->name ?? __('Unassigned')) }}
                                        </p>
                                    </div>
                                    <span class="text-xs text-ink-muted shrink-0">
                                        {{ $lead->next_follow_up_at?->timezone(app(\App\Services\LeadFollowUpService::class)->organizationTimezone())->format('g:i A') }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-workspace.widget>

            <x-workspace.widget
                :title="__('Assigned leads')"
                :subtitle="__('Your open pipeline intake')"
                :href="auth()->user()->hasPermission('leads.view') ? route('leads.index', ['assigned_to' => auth()->id()]) : null"
            >
                @if ($assignedLeads->isEmpty())
                    <x-ui.empty-state-preset
                        variant="leads"
                        :action-href="auth()->user()->can('create', App\Models\Lead::class) ? route('leads.create') : null"
                        class="!py-6"
                    />
                @else
                    <ul class="divide-y divide-line -mx-1">
                        @foreach ($assignedLeads as $lead)
                            <li class="py-2.5 flex items-center justify-between gap-3">
                                <a href="{{ route('leads.show', $lead) }}" class="min-w-0 text-sm font-medium text-ink-heading hover:text-primary-700 truncate">{{ $lead->name }}</a>
                                <x-ui.badge variant="primary">{{ $lead->status_label }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-workspace.widget>

            <x-workspace.widget
                :title="__('Pipeline summary')"
                :subtitle="__('Open stages')"
                :href="auth()->user()->hasPermission('opportunities.view') ? route('pipeline.index') : null"
            >
                @if ($pipelineSummary->isEmpty())
                    <p class="text-sm text-ink-muted py-4 text-center">{{ __('No open opportunities.') }}</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($pipelineSummary as $row)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-ink-heading font-medium">{{ config('pipeline.stages.'.$row['stage'], $row['stage']) }}</span>
                                <span class="text-ink-muted">{{ $row['count'] }} · {{ number_format($row['value'], 0) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-workspace.widget>

            @if ($revenueSummary)
                <x-workspace.widget
                    :title="__('Revenue summary')"
                    :subtitle="__('Collections pulse')"
                    :href="$revenueSummary['href'] ?? null"
                >
                    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-sm">
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Outstanding') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $revenueSummary['outstanding'] !== null ? number_format($revenueSummary['outstanding'], 0) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Invoices') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $revenueSummary['invoices'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Payments') }}</dt>
                            <dd class="mt-1 font-semibold text-ink-heading">{{ $revenueSummary['payments'] }}</dd>
                        </div>
                    </dl>
                </x-workspace.widget>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                <x-workspace.widget
                    :title="__('Recent customers')"
                    :href="auth()->user()->hasPermission('customers.view') ? route('customers.index') : null"
                >
                    @forelse ($recentCustomers as $customer)
                        <a href="{{ route('customers.show', $customer) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $customer->display_name }}</span>
                            <span class="text-xs text-ink-muted shrink-0">{{ $customer->updated_at?->diffForHumans() }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No customers yet.') }}</p>
                    @endforelse
                </x-workspace.widget>

                <x-workspace.widget
                    :title="__('Recent opportunities')"
                    :href="auth()->user()->hasPermission('opportunities.view') ? route('pipeline.index') : null"
                >
                    @forelse ($recentOpportunities as $opportunity)
                        <a href="{{ route('pipeline.show', $opportunity) }}" class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                            <span class="font-medium text-ink-heading truncate hover:text-primary-700">{{ $opportunity->title }}</span>
                            <span class="text-xs text-ink-muted shrink-0">{{ $opportunity->stage_label ?? $opportunity->stage }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-ink-muted py-4 text-center">{{ __('No opportunities yet.') }}</p>
                    @endforelse
                </x-workspace.widget>
            </div>

            <x-workspace.widget
                :title="__('Tasks due today')"
                :href="auth()->user()->hasPermission('tasks.view') && \Illuminate\Support\Facades\Route::has('tasks.index') ? route('tasks.index') : null"
            >
                @forelse ($tasksDueToday as $task)
                    <div class="flex items-center justify-between gap-2 py-2 text-sm border-b border-line last:border-0">
                        <span class="font-medium text-ink-heading truncate">{{ $task->title }}</span>
                        <span class="text-xs text-ink-muted shrink-0">{{ $task->assignee?->name ?? __('Unassigned') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted py-4 text-center">{{ __('No CRM tasks due today.') }}</p>
                @endforelse
            </x-workspace.widget>

            <x-workspace.widget :title="__('Recent CRM activity')">
                @forelse ($recentActivity as $item)
                    <a href="{{ $item['href'] }}" class="flex items-start justify-between gap-3 py-2.5 border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-ink-heading truncate hover:text-primary-700">{{ $item['title'] }}</p>
                            <p class="text-xs text-ink-muted">{{ $item['subtitle'] }}</p>
                        </div>
                        <span class="text-xs text-ink-muted shrink-0">{{ $item['when'] }}</span>
                    </a>
                @empty
                    <x-ui.empty-state-preset variant="activities" class="!py-6" />
                @endforelse
            </x-workspace.widget>
        </div>

        <x-slot:aside>
            <x-workspace.attention-rail :title="__('Needs attention')">
                @forelse ($attention as $item)
                    <x-workspace.attention-item
                        :href="$item['href'] ?? null"
                        :title="$item['title']"
                        :subtitle="$item['subtitle'] ?? null"
                        :badge="$item['badge'] ?? null"
                    />
                @empty
                    {{-- empty slot handled by rail --}}
                @endforelse
            </x-workspace.attention-rail>

            <x-entity.section :title="__('Pinned CRM pages')">
                @forelse ($pinnedPages as $page)
                    <a href="{{ $page['href'] }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ $page['label'] }}</a>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('Pin pages from the shell favorites control.') }}</p>
                @endforelse
            </x-entity.section>

            <x-entity.section :title="__('Favorite reports')">
                @forelse ($favoriteReports as $report)
                    <a href="{{ $report['href'] }}" class="block text-sm font-medium text-primary-600 hover:text-primary-700 py-1.5">{{ $report['label'] }}</a>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('No report shortcuts yet.') }}</p>
                @endforelse
            </x-entity.section>
        </x-slot:aside>
    </x-layouts.workspace-home>
</x-app-layout>
