@php
    $stageVariant = [
        'qualification' => 'info',
        'proposal' => 'primary',
        'negotiation' => 'warning',
        'closed_won' => 'success',
        'closed_lost' => 'neutral',
    ];
    $weightedForecast = ($opportunity->amount !== null && $opportunity->probability !== null)
        ? ((float) $opportunity->amount * (int) $opportunity->probability / 100)
        : null;
    $subtitle = $opportunity->customer?->display_name;
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$opportunity->title"
        :subtitle="$subtitle"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('pipeline'), 'href' => route('pipeline.index')],
                ['label' => $opportunity->title, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('update', $opportunity)
                <x-ui.button :href="route('pipeline.edit', $opportunity)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @can('delete', $opportunity)
                <form method="POST" action="{{ route('pipeline.destroy', $opportunity) }}" onsubmit="return confirm('{{ __('Delete this deal?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$stageVariant[$opportunity->stage] ?? 'neutral'">{{ $opportunity->stage_label }}</x-ui.badge>
                @if ($opportunity->assignee)
                    <span class="text-xs text-ink-muted">{{ __('Assigned to :name', ['name' => $opportunity->assignee->name]) }}</span>
                @endif
            </div>
        </x-slot:tabs>

        @can('update', $opportunity)
            @if ($opportunity->isOpen())
                <x-entity.section :title="__('Pipeline Stage')" :subtitle="__('Move this deal through your pipeline.')">
                    <form method="POST" action="{{ route('pipeline.stage.update', $opportunity) }}" class="mb-4 flex flex-wrap items-center gap-3">
                        @csrf
                        @method('PATCH')
                        <label for="opportunity-stage" class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Stage') }}</label>
                        <x-forms.select id="opportunity-stage" name="stage" onchange="this.form.submit()" class="min-w-[12rem]">
                            @foreach (config('pipeline.open_stages') as $value)
                                <option value="{{ $value }}" @selected($opportunity->stage === $value)>{{ config('pipeline.stages.'.$value) }}</option>
                            @endforeach
                        </x-forms.select>
                    </form>

                    <div class="mb-4 flex flex-wrap items-center gap-2 border-t border-line pt-4">
                        <span class="mr-1 self-center text-xs font-medium text-ink-muted">{{ __('Quick set:') }}</span>
                        @foreach (config('pipeline.open_stages') as $stage)
                            @if ($opportunity->stage !== $stage)
                                <form method="POST" action="{{ route('pipeline.stage.update', $opportunity) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="stage" value="{{ $stage }}">
                                    <x-ui.button type="submit" variant="ghost" size="sm">{{ config('pipeline.stages.'.$stage) }}</x-ui.button>
                                </form>
                            @endif
                        @endforeach
                    </div>

                    <div class="flex flex-wrap gap-2 border-t border-line pt-4">
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            size="sm"
                            x-data
                            x-on:click="$dispatch('open-modal', 'opportunity-mark-won')"
                        >{{ __('Mark as Won') }}</x-ui.button>
                        <x-ui.button
                            type="button"
                            variant="ghost"
                            size="sm"
                            x-data
                            x-on:click="$dispatch('open-modal', 'opportunity-mark-lost')"
                        >{{ __('Mark as Lost') }}</x-ui.button>
                    </div>
                </x-entity.section>
            @else
                <x-entity.section :title="__('Pipeline Stage')">
                    <p class="text-sm text-ink-muted">{{ __('This deal is closed and cannot be moved to another stage.') }}</p>
                </x-entity.section>
            @endif
        @endcan

        <x-entity.section :title="__('Deal details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Value')">
                    @if ($opportunity->amount)
                        {{ $opportunity->currency }} {{ number_format($opportunity->amount, 2) }}
                    @else
                        —
                    @endif
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Probability')">
                    {{ $opportunity->probability !== null ? $opportunity->probability.'%' : '—' }}
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Weighted forecast')">
                    @if ($weightedForecast !== null)
                        {{ $opportunity->currency }} {{ number_format($weightedForecast, 2) }}
                    @else
                        —
                    @endif
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Expected Close')">
                    {{ $opportunity->expected_close_date?->format('M j, Y') ?? '—' }}
                </x-entity.definition-item>
                @if ($opportunity->isWon())
                    <x-entity.definition-item :label="__('Won Date')">
                        <span class="font-semibold text-success">{{ $opportunity->won_at?->format('M j, Y') ?? '—' }}</span>
                    </x-entity.definition-item>
                @endif
                @if ($opportunity->isLost())
                    <x-entity.definition-item :label="__('Lost Reason')" :span="2">
                        <span class="whitespace-pre-wrap">{{ $opportunity->lost_reason ?? '—' }}</span>
                    </x-entity.definition-item>
                @endif
                <x-entity.definition-item :label="__('Assigned To')">
                    {{ $opportunity->assignee?->name ?? __('Unassigned') }}
                </x-entity.definition-item>
                @if ($opportunity->customer)
                    <x-entity.definition-item :label="__('Customer')">
                        <a href="{{ route('customers.show', $opportunity->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $opportunity->customer->display_name }}</a>
                    </x-entity.definition-item>
                @endif
                @if ($opportunity->lead)
                    <x-entity.definition-item :label="__('Related Lead')">
                        <a href="{{ route('leads.show', $opportunity->lead) }}" class="text-primary-600 hover:text-primary-700">{{ $opportunity->lead->name }}</a>
                    </x-entity.definition-item>
                @endif
                @if ($opportunity->description)
                    <x-entity.definition-item :label="__('Description')" :span="2">
                        <span class="whitespace-pre-wrap">{{ $opportunity->description }}</span>
                    </x-entity.definition-item>
                @endif
            </x-entity.definition-list>
        </x-entity.section>

        @include('metadata-fields._runtime_detail', [
            'metadataFields' => $metadataFields ?? collect(),
            'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
            'record' => $opportunity,
        ])

        <x-entity.section :title="__('Activity')" :subtitle="__('Notes and updates')" :padding="true">
            <x-activity.timeline
                :empty="$opportunity->notes->isEmpty()"
                :empty-title="__('No activity yet')"
                :empty-description="__('Add a note to start the timeline.')"
            >
                @can('update', $opportunity)
                    <x-slot:composer>
                        <form id="opportunity-note-form" method="POST" action="{{ route('pipeline.notes.store', $opportunity) }}" class="space-y-3">
                            @csrf
                            <x-forms.field :label="__('Add a note')" name="body" required>
                                <x-forms.textarea id="body" name="body" rows="3" required placeholder="{{ __('Follow-up call scheduled, sent proposal…') }}">{{ old('body') }}</x-forms.textarea>
                            </x-forms.field>
                            <div class="flex justify-end">
                                <x-ui.button type="submit" form="opportunity-note-form" variant="primary" size="sm">{{ __('Add Note') }}</x-ui.button>
                            </div>
                        </form>
                    </x-slot:composer>
                @endcan

                @foreach ($opportunity->notes as $note)
                    <x-activity.timeline-item
                        :actor="$note->user->name"
                        :timestamp="$note->created_at"
                    >{{ $note->body }}</x-activity.timeline-item>
                @endforeach
            </x-activity.timeline>
        </x-entity.section>

        <x-attachments-panel
            attachable-type="opportunity"
            :attachable-id="$opportunity->id"
            :attachments="$opportunity->attachments"
            :can-upload="auth()->user()->can('attachments.create')"
            :can-delete="auth()->user()->can('attachments.delete')"
        />

        <x-tasks-panel
            taskable-type="opportunity"
            :taskable-id="$opportunity->id"
            :tasks="$opportunity->tasks"
            :can-create="auth()->user()->can('tasks.create')"
        />

        <x-slot:aside>
            <x-entity.section :title="__('Forecast')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Deal value') }}</dt>
                        <dd class="mt-1 font-medium text-ink-heading">
                            @if ($opportunity->amount)
                                {{ $opportunity->currency }} {{ number_format($opportunity->amount, 2) }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Probability') }}</dt>
                        <dd class="mt-1 text-ink-heading">{{ $opportunity->probability !== null ? $opportunity->probability.'%' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Weighted forecast') }}</dt>
                        <dd class="mt-1 font-semibold text-ink-heading">
                            @if ($weightedForecast !== null)
                                {{ $opportunity->currency }} {{ number_format($weightedForecast, 2) }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Expected close') }}</dt>
                        <dd class="mt-1 text-ink-heading">{{ $opportunity->expected_close_date?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </x-entity.section>

            <x-entity.section :title="__('Audit')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Created') }}</dt>
                        <dd class="text-ink-heading">{{ $opportunity->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Last updated') }}</dt>
                        <dd class="text-ink-heading">{{ $opportunity->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    @if ($opportunity->creator)
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Created by') }}</dt>
                            <dd class="text-ink-heading">{{ $opportunity->creator->name }}</dd>
                        </div>
                    @endif
                </dl>
            </x-entity.section>

            <x-ui.button :href="route('pipeline.index')" variant="link" size="sm">← {{ __('Back to pipeline') }}</x-ui.button>
        </x-slot:aside>
    </x-layouts.entity-detail>

    @can('update', $opportunity)
        @if ($opportunity->isOpen())
            <x-opportunity-close-modal :opportunity="$opportunity" />
        @endif
    @endcan
</x-app-layout>
