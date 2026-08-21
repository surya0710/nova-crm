@php
    $statusVariant = [
        'prospect' => 'info',
        'active' => 'success',
        'inactive' => 'neutral',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$customer->display_name"
        :subtitle="$customer->name"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('customers'), 'href' => route('customers.index')],
                ['label' => $customer->display_name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('update', $customer)
                <x-ui.button :href="route('customers.show', $customer).'#email-composer'" variant="secondary" size="sm">{{ __('Email') }}</x-ui.button>
                <x-ui.button :href="route('customers.edit', $customer)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @can('delete', $customer)
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('{{ __('Delete this customer?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge :variant="$statusVariant[$customer->status] ?? 'neutral'">{{ $customer->status_label }}</x-ui.badge>
            <x-ui.badge variant="primary">{{ $customer->lifecycle_stage_label }}</x-ui.badge>
            @if ($customer->assignee)
                <span class="ms-2 text-xs text-ink-muted">{{ __('Managed by :name', ['name' => $customer->assignee->name]) }}</span>
            @endif
        </x-slot:tabs>

        <x-entity.section :title="__('Customer details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Contact')">{{ $customer->name }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Company')">{{ $customer->company ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Email')">{{ $customer->email ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Phone')">{{ $customer->phone ?? '—' }}</x-entity.definition-item>
                @if ($customer->website)
                    <x-entity.definition-item :label="__('Website')" :span="2">
                        <a href="{{ $customer->website }}" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700">{{ $customer->website }}</a>
                    </x-entity.definition-item>
                @endif
                <x-entity.definition-item :label="__('Industry')">{{ $customer->industry ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Type')">{{ $customer->type_label }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Lifecycle')">{{ $customer->lifecycle_stage_label }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Segment')">{{ $customer->segment ? $customer->segment_label : '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Source')">{{ $customer->source ? $customer->source_label : '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Account Manager')">{{ $customer->assignee?->name ?? __('Unassigned') }}</x-entity.definition-item>
                @if ($customer->tax_number)
                    <x-entity.definition-item :label="__('Tax Number')">{{ $customer->tax_number }}</x-entity.definition-item>
                @endif
                @if ($customer->gstin)
                    <x-entity.definition-item :label="__('GSTIN')">{{ $customer->gstin }}</x-entity.definition-item>
                @endif
                @if ($customer->pan)
                    <x-entity.definition-item :label="__('PAN')">{{ $customer->pan }}</x-entity.definition-item>
                @endif
                @if ($customer->lead)
                    <x-entity.definition-item :label="__('Converted From Lead')">
                        <a href="{{ route('leads.show', $customer->lead) }}" class="text-primary-600 hover:text-primary-700">{{ $customer->lead->name }}</a>
                    </x-entity.definition-item>
                @endif
                @if ($customer->tags)
                    <x-entity.definition-item :label="__('Tags')" :span="2">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($customer->tags as $tag)
                                <x-ui.badge variant="primary">{{ $tag }}</x-ui.badge>
                            @endforeach
                        </div>
                    </x-entity.definition-item>
                @endif
            </x-entity.definition-list>
        </x-entity.section>

        @include('metadata-fields._runtime_detail', [
            'metadataFields' => $metadataFields ?? collect(),
            'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
            'record' => $customer,
        ])

        @include('customers._relationships')

        @if ($statement)
            <x-entity.section :title="__('Account Statement')" :subtitle="__('Read-only financial summary')">
                <x-slot:actions>
                    @if (auth()->user()->hasPermission('reports.manage') || auth()->user()->hasPermission('finance.view'))
                        <x-ui.button :href="route('customers.statement.export', $customer)" variant="link" size="sm">{{ __('Export CSV') }}</x-ui.button>
                    @endif
                </x-slot:actions>
                @php
                    $stmtCurrency = $statement['currency'];
                    $fmt = fn (float $amount) => number_format($amount, 2).' '.$stmtCurrency;
                @endphp
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-ui.stat-card :label="__('Total invoiced')" :value="$fmt($statement['total_invoiced'])" />
                    <x-ui.stat-card :label="__('Total paid')" :value="$fmt($statement['total_paid'])" />
                    <x-ui.stat-card :label="__('Balance due')" :value="$fmt($statement['balance_due'])" />
                </div>
                @if (! empty($statement['total_credits']) || ! empty($statement['total_debits']))
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <x-ui.stat-card :label="__('Credits applied')" :value="$fmt((float) ($statement['total_credits'] ?? 0))" />
                        <x-ui.stat-card :label="__('Debits applied')" :value="$fmt((float) ($statement['total_debits'] ?? 0))" />
                        <x-ui.stat-card :label="__('Outstanding')" :value="$fmt((float) ($statement['outstanding_balance'] ?? $statement['balance_due']))" />
                    </div>
                @endif
                @if (! empty($statement['status_counts']))
                    <div class="mb-6 flex flex-wrap gap-2 text-xs">
                        @foreach ($statement['status_counts'] as $status => $count)
                            <x-ui.badge variant="neutral">{{ __(ucfirst($status)) }}: {{ $count }}</x-ui.badge>
                        @endforeach
                    </div>
                @endif
                @if (! empty($statement['aging']))
                    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
                        @foreach ($statement['aging'] as $bucket)
                            <div class="rounded-lg border border-line px-3 py-2">
                                <p class="text-xs text-ink-muted">{{ $bucket['label'] }}</p>
                                <p class="text-sm font-semibold text-ink-heading">{{ $fmt((float) $bucket['total']) }}</p>
                                <p class="text-xs text-ink-muted">{{ trans_choice(':count invoice|:count invoices', $bucket['count'], ['count' => $bucket['count']]) }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if ($statement['ledger']->isEmpty())
                    <p class="py-6 text-center text-sm text-ink-muted">{{ __('No invoices or payments yet.') }}</p>
                @else
                    <x-tables.table :columns="[__('Date'), __('Type'), __('Reference'), ['label' => __('Debit'), 'align' => 'right'], ['label' => __('Credit'), 'align' => 'right'], ['label' => __('Balance'), 'align' => 'right']]" :sticky="false">
                        @foreach ($statement['ledger'] as $entry)
                            <tr>
                                <td class="px-4 py-2 text-sm text-ink">{{ $entry['date']?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-4 py-2">
                                    @php
                                        $ledgerType = $entry['type'];
                                        $ledgerLabel = match ($ledgerType) {
                                            'invoice' => __('Invoice'),
                                            'payment' => __('Payment'),
                                            'credit_note' => __('Credit note'),
                                            'debit_note' => __('Debit note'),
                                            default => ucfirst(str_replace('_', ' ', $ledgerType)),
                                        };
                                        $ledgerVariant = match ($ledgerType) {
                                            'invoice', 'debit_note' => 'primary',
                                            'credit_note' => 'warning',
                                            default => 'success',
                                        };
                                    @endphp
                                    <x-ui.badge :variant="$ledgerVariant">{{ $ledgerLabel }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-2 text-sm">
                                    @if ($ledgerType === 'invoice' && isset($entry['invoice_id']))
                                        <a href="{{ route('invoices.show', $entry['invoice_id']) }}" class="text-primary-600 hover:text-primary-700">{{ $entry['number'] }}</a>
                                    @elseif ($ledgerType === 'payment' && isset($entry['payment_id']))
                                        <a href="{{ route('payments.show', $entry['payment_id']) }}" class="text-primary-600 hover:text-primary-700">{{ $entry['number'] }}</a>
                                    @elseif (in_array($ledgerType, ['credit_note', 'debit_note'], true) && isset($entry['adjustment_note_id']))
                                        <a href="{{ route($ledgerType === 'credit_note' ? 'credit-notes.show' : 'debit-notes.show', $entry['adjustment_note_id']) }}" class="text-primary-600 hover:text-primary-700">{{ $entry['number'] }}</a>
                                    @else
                                        {{ $entry['number'] }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right text-sm">{{ $entry['debit'] > 0 ? $fmt($entry['debit']) : '—' }}</td>
                                <td class="px-4 py-2 text-right text-sm text-success">{{ $entry['credit'] > 0 ? $fmt($entry['credit']) : '—' }}</td>
                                <td class="px-4 py-2 text-right text-sm font-medium">{{ $fmt($entry['balance']) }}</td>
                            </tr>
                        @endforeach
                    </x-tables.table>
                @endif
            </x-entity.section>
        @endif

        @if ($customer->gstin || $customer->gst_registration_type || $customer->place_of_supply || $customer->isTaxExempt())
            <x-entity.section :title="__('GST / Tax Profile')">
                <x-entity.definition-list>
                    <x-entity.definition-item :label="__('GST registration')">{{ $customer->gst_registration_type_label ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Registration status')">{{ $customer->tax_registration_status ? config('tax.tax_registration_statuses.'.$customer->tax_registration_status) : '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Place of supply')">{{ $customer->place_of_supply_label ?? '—' }}</x-entity.definition-item>
                    <x-entity.definition-item :label="__('Tax preference')">{{ $customer->default_tax_preference ? config('tax.tax_preferences.'.$customer->default_tax_preference) : __('Tax exclusive') }}</x-entity.definition-item>
                    @if ($customer->isTaxExempt())
                        <x-entity.definition-item :label="__('Exemption')" :span="2">{{ $customer->tax_exemption_reason ?: __('Exempt') }}</x-entity.definition-item>
                    @endif
                </x-entity.definition-list>
            </x-entity.section>
        @endif

        @if ($customer->address_line_1 || $customer->address_line_2 || $customer->city || $customer->state || $customer->country || $customer->postal_code)
            <x-entity.section :title="__('Billing Address')">
                <div class="text-sm leading-relaxed text-ink">
                    @if ($customer->address_line_1){{ $customer->address_line_1 }}<br>@endif
                    @if ($customer->address_line_2){{ $customer->address_line_2 }}<br>@endif
                    @if ($customer->city || $customer->state || $customer->postal_code)
                        {{ collect([$customer->city, $customer->state, $customer->postal_code])->filter()->join(', ') }}<br>
                    @endif
                    @if ($customer->country){{ $customer->country }}@endif
                </div>
            </x-entity.section>
        @endif

        @if (! $customer->shipping_same_as_billing && ($customer->shipping_address_line_1 || $customer->shipping_city || $customer->shipping_state))
            <x-entity.section :title="__('Shipping Address')">
                <div class="text-sm leading-relaxed text-ink">
                    @foreach ($customer->shipping_address_lines as $line)
                        {{ $line }}<br>
                    @endforeach
                </div>
            </x-entity.section>
        @endif

        <x-entity.section id="activity" class="scroll-mt-24" :title="__('Activity')">
            <x-activity.timeline
                :empty="($timelineItems ?? collect())->isEmpty()"
                :empty-title="__('No activity yet')"
                :empty-description="__('Add a note or send a quotation to start the timeline.')"
            >
                @can('update', $customer)
                    <x-slot:composer>
                        <form method="POST" action="{{ route('customers.notes.store', $customer) }}" class="space-y-3">
                            @csrf
                            <x-forms.field :label="__('Add a note')" name="body" required>
                                <x-forms.textarea id="body" name="body" rows="3" required>{{ old('body') }}</x-forms.textarea>
                            </x-forms.field>
                            <div class="flex justify-end gap-2">
                                <x-ui.button :href="'#email-composer'" variant="secondary" size="sm">{{ __('Email') }}</x-ui.button>
                                <x-ui.button type="submit" variant="primary" size="sm">{{ __('Add Note') }}</x-ui.button>
                            </div>
                        </form>
                    </x-slot:composer>
                @endcan
                @foreach ($timelineItems ?? [] as $item)
                    <x-activity.timeline-item :actor="$item['actor']" :timestamp="$item['timestamp']">
                        <x-slot:label>{{ $item['label'] }}</x-slot:label>
                        @if (! empty($item['href']))
                            <a href="{{ $item['href'] }}" class="text-primary-600 hover:text-primary-700">{{ $item['body'] }}</a>
                        @else
                            {{ $item['body'] }}
                        @endif
                    </x-activity.timeline-item>
                @endforeach
            </x-activity.timeline>
        </x-entity.section>

        <x-attachments-panel
            attachable-type="customer"
            :attachable-id="$customer->id"
            :attachments="$customer->attachments"
            :can-upload="auth()->user()->can('attachments.create')"
            :can-delete="auth()->user()->can('attachments.delete')"
        />

        <x-crm-email-conversations :related="$customer" />

        <x-tasks-panel
            taskable-type="customer"
            :taskable-id="$customer->id"
            :tasks="$customer->tasks"
            :can-create="auth()->user()->can('tasks.create')"
        />

        @can('update', $customer)
            <x-client-email-form
                :action="route('customers.send', $customer)"
                :email="old('email', $customer->email ?? $customer->primaryContact?->email)"
                :submit-label="__('Send Email')"
                :title="__('Email Client')"
                :description="__('Send a message to this customer with optional file attachments')"
                :organization="$organization"
                :show-subject="true"
                :subject="old('subject', __('Message from :name', ['name' => $organization?->name ?? config('app.name')]))"
                :missing-email-hint="! $customer->email && ! $customer->primaryContact?->email"
                :show-bcc="true"
                module="customers"
                :related="$customer"
                :suggested-recipients="$customer->contacts->pluck('email')->push($customer->email)->filter()->unique()->values()"
            />
        @endcan

        <x-slot:aside>
            <x-entity.section :title="__('Audit')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Created') }}</dt>
                        <dd class="text-ink-heading">{{ $customer->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Last updated') }}</dt>
                        <dd class="text-ink-heading">{{ $customer->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    @if ($customer->creator)
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Created by') }}</dt>
                            <dd class="text-ink-heading">{{ $customer->creator->name }}</dd>
                        </div>
                    @endif
                </dl>
            </x-entity.section>
            <x-ui.button :href="route('customers.index')" variant="link" size="sm">← {{ __('Back to customers') }}</x-ui.button>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
