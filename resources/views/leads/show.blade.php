@php
    $statusVariant = [
        'new' => 'info',
        'contacted' => 'info',
        'qualified' => 'primary',
        'proposal_sent' => 'primary',
        'negotiation' => 'warning',
        'converted' => 'success',
        'won' => 'success',
        'lost' => 'neutral',
    ];
    $priorityVariant = [
        'low' => 'neutral',
        'medium' => 'warning',
        'high' => 'danger',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$lead->name"
        :subtitle="$lead->company"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('leads'), 'href' => route('leads.index')],
                ['label' => $lead->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('convert', $lead)
                @if ($lead->isConvertible())
                    <x-ui.button
                        type="button"
                        variant="secondary"
                        size="sm"
                        x-data
                        x-on:click="$dispatch('open-modal', 'convert-lead')"
                    >{{ __('Convert Lead') }}</x-ui.button>
                @endif
            @endcan
            @can('update', $lead)
                <x-ui.button :href="route('leads.edit', $lead)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @can('delete', $lead)
                <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('{{ __('Delete this lead?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.badge :variant="$statusVariant[$lead->status] ?? 'neutral'">{{ $lead->status_label }}</x-ui.badge>
                <x-ui.badge :variant="$priorityVariant[$lead->priority] ?? 'neutral'">{{ $lead->priority_label }}</x-ui.badge>
                @if ($lead->assignee)
                    <span class="text-xs text-ink-muted">{{ __('Assigned to :name', ['name' => $lead->assignee->name]) }}</span>
                @endif
            </div>
        </x-slot:tabs>

        <x-entity.section :title="__('Lead details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Email')">{{ $lead->email ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Phone')">{{ $lead->phone ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Source')">{{ $lead->source_label }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Industry')">{{ $lead->industry ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Budget')">{{ $lead->budget ? number_format($lead->budget, 2) : '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Created by')">{{ $lead->creator?->name ?? '—' }}</x-entity.definition-item>
                @if ($lead->tags)
                    <x-entity.definition-item :label="__('Tags')" :span="2">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($lead->tags as $tag)
                                <x-ui.badge variant="primary">{{ $tag }}</x-ui.badge>
                            @endforeach
                        </div>
                    </x-entity.definition-item>
                @endif
            </x-entity.definition-list>
        </x-entity.section>

        @if ($lead->address_line_1 || $lead->city || $lead->state || $lead->country || $lead->postal_code)
            <x-entity.section :title="__('Address Information')">
                <address class="text-sm not-italic leading-6 text-ink">
                    @if ($lead->address_line_1){{ $lead->address_line_1 }}<br>@endif
                    @if ($lead->city || $lead->state || $lead->postal_code)
                        {{ collect([$lead->city, $lead->state, $lead->postal_code])->filter()->join(', ') }}<br>
                    @endif
                    @if ($lead->country){{ $lead->country }}@endif
                </address>
            </x-entity.section>
        @endif

        @include('metadata-fields._runtime_detail', [
            'metadataFields' => $metadataFields ?? collect(),
            'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
            'record' => $lead,
        ])

        <x-entity.section :title="__('Activity')" :subtitle="__('Notes and follow-ups')" :padding="true">
            <x-activity.timeline
                :empty="$lead->notes->isEmpty()"
                :empty-title="__('No activity yet')"
                :empty-description="__('Add a note to start the timeline.')"
            >
                @can('update', $lead)
                    <x-slot:composer>
                        <form id="lead-note-form" method="POST" action="{{ route('leads.notes.store', $lead) }}" class="space-y-3">
                            @csrf
                            <x-forms.field :label="__('Add a note')" name="body" required>
                                <x-forms.textarea id="body" name="body" rows="3" required placeholder="{{ __('Follow-up call scheduled, sent proposal…') }}">{{ old('body') }}</x-forms.textarea>
                            </x-forms.field>
                        </form>

                        <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <form method="POST" action="{{ route('leads.status.update', $lead) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <label for="lead-status" class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Status') }}</label>
                                <x-forms.select id="lead-status" name="status" onchange="this.form.submit()" class="min-w-[10rem]">
                                    @foreach (config('leads.statuses') as $value => $label)
                                        <option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </x-forms.select>
                            </form>

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                @foreach ([
                                    'contacted' => __('Contacted'),
                                    'qualified' => __('Qualified'),
                                    'won' => __('Won'),
                                    'lost' => __('Lost'),
                                ] as $status => $label)
                                    @if ($lead->status !== $status)
                                        <form method="POST" action="{{ route('leads.status.update', $lead) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $status }}">
                                            <x-ui.button type="submit" variant="ghost" size="sm">{{ $label }}</x-ui.button>
                                        </form>
                                    @endif
                                @endforeach
                                <x-ui.button type="submit" form="lead-note-form" variant="primary" size="sm">{{ __('Add Note') }}</x-ui.button>
                            </div>
                        </div>
                    </x-slot:composer>
                @endcan

                @foreach ($lead->notes as $note)
                    <x-activity.timeline-item
                        :actor="$note->user->name"
                        :timestamp="$note->created_at"
                    >{{ $note->body }}</x-activity.timeline-item>
                @endforeach
            </x-activity.timeline>
        </x-entity.section>

        <x-attachments-panel
            attachable-type="lead"
            :attachable-id="$lead->id"
            :attachments="$lead->attachments"
            :can-upload="auth()->user()->can('attachments.create')"
            :can-delete="auth()->user()->can('attachments.delete')"
        />

        <x-tasks-panel
            taskable-type="lead"
            :taskable-id="$lead->id"
            :tasks="$lead->tasks"
            :can-create="auth()->user()->can('tasks.create')"
        />

        <x-slot:aside>
            <x-entity.section :title="__('Next Follow-up')">
                @if ($lead->hasFollowUpScheduled())
                    <dl class="mb-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Scheduled') }}</dt>
                            <dd @class(['mt-1 font-medium', 'text-warning' => $lead->isFollowUpDue(), 'text-ink-heading' => ! $lead->isFollowUpDue()])>
                                {{ $lead->next_follow_up_at->timezone(app(\App\Services\LeadFollowUpService::class)->organizationTimezone())->format('M j, Y g:i A') }}
                                @if ($lead->isFollowUpDue())
                                    <span class="ml-1 text-xs font-semibold uppercase text-warning">({{ __('Due now') }})</span>
                                @endif
                            </dd>
                        </div>
                        @if ($lead->next_follow_up_note)
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('Notes') }}</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-ink">{{ $lead->next_follow_up_note }}</dd>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="mb-4 text-sm text-ink-muted">{{ __('No follow-up scheduled.') }}</p>
                @endif

                @can('update', $lead)
                    @php
                        $followUpService = app(\App\Services\LeadFollowUpService::class);
                        $followUpInputValue = old('next_follow_up_at');
                        if ($followUpInputValue === null) {
                            if ($lead->hasFollowUpScheduled() && ! $lead->isFollowUpDue()) {
                                $followUpInputValue = $followUpService->formatForInput($lead->next_follow_up_at);
                            } else {
                                $followUpInputValue = $followUpService->formatForInput(
                                    $followUpService->organizationNow()->copy()->addHour()
                                );
                            }
                        }
                    @endphp

                    <form method="POST" action="{{ route('leads.follow-up.update', $lead) }}" class="space-y-3 border-t border-line pt-4">
                        @csrf
                        @method('PATCH')
                        <x-forms.field :label="__('Date & Time')" name="next_follow_up_at">
                            <x-follow-up-datetime-input
                                id="next_follow_up_at"
                                :value="$followUpInputValue"
                                show-quick-pick
                                class="mt-1"
                            />
                        </x-forms.field>
                        <x-forms.field :label="__('Notes')" name="next_follow_up_note">
                            <x-forms.textarea id="next_follow_up_note" name="next_follow_up_note" rows="2" placeholder="{{ __('What to discuss on the call…') }}">{{ old('next_follow_up_note', $lead->next_follow_up_note) }}</x-forms.textarea>
                        </x-forms.field>
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Save Follow-up') }}</x-ui.button>
                    </form>

                    @if ($lead->hasFollowUpScheduled())
                        <form method="POST" action="{{ route('leads.follow-up.update', $lead) }}" class="mt-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="next_follow_up_at" value="">
                            <input type="hidden" name="next_follow_up_note" value="">
                            <button type="submit" class="text-sm text-ink-muted hover:text-danger">{{ __('Clear schedule') }}</button>
                        </form>
                    @endif
                @endcan
            </x-entity.section>

            <x-entity.section :title="__('Audit')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Created') }}</dt>
                        <dd class="text-ink-heading">{{ $lead->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Last updated') }}</dt>
                        <dd class="text-ink-heading">{{ $lead->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                </dl>
            </x-entity.section>

            <x-ui.button :href="route('leads.index')" variant="link" size="sm">← {{ __('Back to leads') }}</x-ui.button>
        </x-slot:aside>
    </x-layouts.entity-detail>

    @can('convert', $lead)
        @if ($lead->isConvertible())
            <x-lead-convert-modal :lead="$lead" />
        @endif
    @endcan
</x-app-layout>
