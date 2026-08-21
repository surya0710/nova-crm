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
                <x-ui.button :href="route('pipeline.show', $opportunity).'#email-composer'" variant="secondary" size="sm">{{ __('Email') }}</x-ui.button>
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
                <x-entity.definition-item :label="__('Source')">
                    {{ $opportunity->source ? (config('customers.sources.'.$opportunity->source) ?? $opportunity->source) : '—' }}
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Competitor')">
                    {{ $opportunity->competitor ?: '—' }}
                </x-entity.definition-item>
                <x-entity.definition-item :label="__('Next activity')">
                    @if ($opportunity->next_activity_at)
                        {{ $opportunity->next_activity_subject ?: config('crm_activities.types.'.$opportunity->next_activity_type) }}
                        · {{ $opportunity->next_activity_at->format('M j, Y g:i A') }}
                    @else
                        —
                    @endif
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

        <x-entity.section :title="__('Commercial chain')" :subtitle="__('Lead → opportunity → quotation → sales order → invoice → payment')">
            <x-slot:actions>
                @can('create', App\Models\Quotation::class)
                    @if ($opportunity->customer_id)
                        <x-ui.button :href="route('quotations.create', ['opportunity' => $opportunity->id])" variant="primary" size="sm">{{ __('New quotation') }}</x-ui.button>
                    @endif
                @endcan
            </x-slot:actions>
            @if ($opportunity->quotations->isEmpty() && $opportunity->salesOrders->isEmpty() && $opportunity->invoices->isEmpty())
                <p class="py-4 text-sm text-ink-muted">{{ __('No quotations, sales orders, or invoices are linked to this deal yet.') }}</p>
            @else
                <div class="space-y-4">
                    @if ($opportunity->quotations->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ crm_term('quotations') }}</p>
                            <ul class="space-y-1 text-sm">
                                @foreach ($opportunity->quotations as $quotation)
                                    <li>
                                        <a href="{{ route('quotations.show', $quotation) }}" class="text-primary-600 hover:text-primary-700">{{ $quotation->number }}</a>
                                        <span class="text-ink-muted">· {{ $quotation->status_label }} · {{ $quotation->formatted_total }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if ($opportunity->salesOrders->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ crm_term('sales_orders') }}</p>
                            <ul class="space-y-1 text-sm">
                                @foreach ($opportunity->salesOrders as $salesOrder)
                                    <li>
                                        <a href="{{ route('sales-orders.show', $salesOrder) }}" class="text-primary-600 hover:text-primary-700">{{ $salesOrder->number }}</a>
                                        <span class="text-ink-muted">· {{ $salesOrder->status_label }} · {{ $salesOrder->formatted_total }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if ($opportunity->invoices->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ crm_term('invoices') }}</p>
                            <ul class="space-y-1 text-sm">
                                @foreach ($opportunity->invoices as $invoice)
                                    <li>
                                        <a href="{{ route('invoices.show', $invoice) }}" class="text-primary-600 hover:text-primary-700">{{ $invoice->number }}</a>
                                        <span class="text-ink-muted">· {{ $invoice->status_label }} · {{ $invoice->formatted_total }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Deal contacts')">
            @if ($opportunity->contacts->isEmpty())
                <p class="text-sm text-ink-muted">{{ __('No contacts are linked to this deal yet.') }}</p>
            @else
                <ul class="space-y-1 text-sm">
                    @foreach ($opportunity->contacts as $link)
                        <li>
                            @if ($link->contact)
                                <a href="{{ route('contacts.show', $link->contact) }}" class="text-primary-600 hover:text-primary-700">{{ $link->contact->name }}</a>
                            @endif
                            <span class="text-ink-muted">· {{ $link->role_label }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-entity.section>

        <x-entity.section :title="__('Products / services')">
            @if ($opportunity->products->isEmpty())
                <p class="text-sm text-ink-muted">{{ __('No products or services are on this deal.') }}</p>
            @else
                <ul class="space-y-1 text-sm">
                    @foreach ($opportunity->products as $item)
                        <li>{{ $item->name }} · {{ $item->quantity }} × {{ number_format($item->unit_price, 2) }} = {{ number_format($item->amount, 2) }}</li>
                    @endforeach
                </ul>
            @endif
        </x-entity.section>

        @include('metadata-fields._runtime_detail', [
            'metadataFields' => $metadataFields ?? collect(),
            'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
            'record' => $opportunity,
        ])

        <x-entity.section :title="__('Activity')" :subtitle="__('Notes and updates')" :padding="true">
            <x-activity.timeline
                :empty="$opportunity->notes->isEmpty() && $opportunity->activities->isEmpty()"
                :empty-title="__('No activity yet')"
                :empty-description="__('Log a call, meeting, or note to start the timeline.')"
            >
                @can('update', $opportunity)
                    <x-slot:composer>
                        <form method="POST" action="{{ route('pipeline.activities.store', $opportunity) }}" class="mb-4 space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <x-forms.select name="type" required>
                                    @foreach (config('crm_activities.types') ?? [] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type', 'follow_up') === $value)>{{ $label }}</option>
                                    @endforeach
                                </x-forms.select>
                                <x-forms.input name="subject" :value="old('subject')" placeholder="{{ __('Subject') }}" />
                                <x-forms.input type="datetime-local" name="due_at" :value="old('due_at')" />
                            </div>
                            <x-forms.textarea name="body" rows="2" placeholder="{{ __('Notes') }}">{{ old('activity_body') }}</x-forms.textarea>
                            <div class="flex justify-end">
                                <x-ui.button type="submit" variant="secondary" size="sm">{{ __('Log activity') }}</x-ui.button>
                            </div>
                        </form>
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

                @foreach ($opportunity->activities as $activity)
                    <x-activity.timeline-item
                        :actor="$activity->assignee?->name"
                        :timestamp="$activity->occurred_at ?? $activity->created_at"
                    >
                        <x-slot:label>{{ $activity->type_label }}</x-slot:label>
                        {{ $activity->subject }}
                    </x-activity.timeline-item>
                @endforeach
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

        <x-crm-email-conversations :related="$opportunity" />

        @can('update', $opportunity)
            <x-client-email-form
                :action="route('pipeline.send', $opportunity)"
                :email="old('email', $opportunity->customer?->email ?? $opportunity->customer?->primaryContact?->email)"
                :submit-label="__('Send Email')"
                :title="__('Email about this deal')"
                :organization="$organization ?? $opportunity->organization"
                :show-subject="true"
                :subject="old('subject', $opportunity->title)"
                :missing-email-hint="! $opportunity->customer?->email"
                :show-bcc="true"
                module="opportunities"
                :related="$opportunity"
                :suggested-recipients="$opportunity->contacts->pluck('contact.email')->push($opportunity->customer?->email)->filter()->unique()->values()"
            />
        @endcan

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
