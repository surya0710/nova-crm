@php
    $statusVariant = [
        'draft' => 'neutral',
        'sent' => 'info',
        'accepted' => 'success',
        'rejected' => 'danger',
        'expired' => 'warning',
        'converted' => 'primary',
    ];

    $allowedTransitions = $quotation->allowedTransitions();
    $selectableStatuses = array_unique(array_merge([$quotation->status], $allowedTransitions));
    $quickActions = [
        'sent' => ['label' => __('Mark Sent'), 'variant' => 'secondary'],
        'accepted' => ['label' => __('Accepted'), 'variant' => 'primary'],
        'rejected' => ['label' => __('Rejected'), 'variant' => 'danger'],
        'expired' => ['label' => __('Expired'), 'variant' => 'secondary'],
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$quotation->number"
        :subtitle="collect([$quotation->customer->display_name, $quotation->title])->filter()->implode(' · ')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('quotations'), 'href' => route('quotations.index')],
                ['label' => $quotation->number, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('update', $quotation)
                <x-ui.button :href="route('quotations.edit', $quotation)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @if ($quotation->canConvert())
                @can('convert', $quotation)
                    <form method="POST" action="{{ route('quotations.convert', $quotation) }}">
                        @csrf
                        <x-ui.button type="submit" variant="primary" size="sm">{{ __('Generate :label', ['label' => crm_term('invoice')]) }}</x-ui.button>
                    </form>
                @endcan
            @elseif ($quotation->status === 'converted' && $quotation->invoice)
                @can('view', $quotation->invoice)
                    <x-ui.button :href="route('invoices.show', $quotation->invoice)" variant="secondary" size="sm">{{ __('View :label', ['label' => crm_term('invoice')]) }}</x-ui.button>
                @endcan
            @endif
            @can('delete', $quotation)
                <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" onsubmit="return confirm('{{ __('Delete this quotation?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge :variant="$statusVariant[$quotation->status] ?? 'neutral'">{{ $quotation->status_label }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Line Items')">
            <x-tables.table :columns="[
                __('Description'),
                ['label' => __('Qty'), 'align' => 'right'],
                ['label' => __('Price'), 'align' => 'right'],
                ['label' => __('Total'), 'align' => 'right'],
            ]" :sticky="false">
                @foreach ($quotation->items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="text-sm text-ink-heading">{{ $item->description }}</p>
                            @if ($item->product)
                                <p class="mt-0.5 text-xs text-ink-muted">{{ $item->product->sku ?? $item->product->name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-ink-muted">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm text-ink-muted">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium text-ink-heading">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </x-tables.table>
            <dl class="mt-4 max-w-xs ms-auto space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-ink-muted">{{ __('Subtotal') }}</dt>
                    <dd class="text-ink-heading">{{ number_format((float) $quotation->subtotal, 2) }} {{ $quotation->currency }}</dd>
                </div>
                @if ((float) $quotation->discount_amount > 0)
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">{{ __('Discount') }}</dt>
                        <dd class="text-ink-heading">-{{ number_format((float) $quotation->discount_amount, 2) }} {{ $quotation->currency }}</dd>
                    </div>
                @endif
                @if ((float) $quotation->tax_total > 0)
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">{{ __('Tax') }}</dt>
                        <dd class="text-ink-heading">{{ number_format((float) $quotation->tax_total, 2) }} {{ $quotation->currency }}</dd>
                    </div>
                @endif
                <div class="flex justify-between border-t border-line pt-2">
                    <dt class="font-semibold text-ink-heading">{{ __('Total') }}</dt>
                    <dd class="font-bold text-ink-heading">{{ $quotation->formatted_total }}</dd>
                </div>
            </dl>
        </x-entity.section>

        @if ($quotation->notes)
            <x-entity.section :title="__('Terms & Notes')">
                <div class="text-sm whitespace-pre-line text-ink">{{ $quotation->notes }}</div>
            </x-entity.section>
        @endif

        @can('changeStatus', $quotation)
            <x-client-email-form
                :action="route('quotations.send', $quotation)"
                :email="old('email', $quotation->customer->email)"
                :submit-label="__('Send Quotation')"
                :description="__('Email this quotation to your customer')"
                :organization="$organization"
                :missing-email-hint="! $quotation->customer->email"
            />

            @if ($allowedTransitions !== [])
                <x-entity.section :title="__('Update Status')">
                    <form method="POST" action="{{ route('quotations.status.update', $quotation) }}" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        @csrf
                        @method('PATCH')
                        <x-forms.field :label="__('Status')" name="status" class="sm:min-w-[160px]">
                            <x-forms.select
                                id="quotation-status"
                                name="status"
                                onchange="this.form.submit()"
                            >
                                @foreach ($selectableStatuses as $value)
                                    <option value="{{ $value }}" @selected($quotation->status === $value)>{{ config('quotations.statuses.'.$value) }}</option>
                                @endforeach
                            </x-forms.select>
                        </x-forms.field>
                    </form>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($quickActions as $status => $meta)
                            @if (in_array($status, $allowedTransitions, true))
                                <form method="POST" action="{{ route('quotations.status.update', $quotation) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <x-ui.button type="submit" :variant="$meta['variant']" size="sm">{{ $meta['label'] }}</x-ui.button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </x-entity.section>
            @endif
        @endcan

        <x-attachments-panel
            attachable-type="quotation"
            :attachable-id="$quotation->id"
            :attachments="$quotation->attachments"
            :can-upload="auth()->user()->can('attachments.create')"
            :can-delete="auth()->user()->can('attachments.delete')"
        />

        <x-slot:aside>
            <x-entity.section :title="__('Details')">
                <x-entity.definition-list>
                    <x-entity.definition-item :label="__('Customer')" :span="2">
                        <a href="{{ route('customers.show', $quotation->customer) }}" class="text-primary-600 hover:text-primary-700">{{ $quotation->customer->display_name }}</a>
                    </x-entity.definition-item>
                    @if ($quotation->opportunity)
                        <x-entity.definition-item :label="__('Deal')" :span="2">
                            <a href="{{ route('pipeline.show', $quotation->opportunity) }}" class="text-primary-600 hover:text-primary-700">{{ $quotation->opportunity->title }}</a>
                        </x-entity.definition-item>
                    @endif
                    <x-entity.definition-item :label="__('Issue Date')">{{ $quotation->issue_date->format('M j, Y') }}</x-entity.definition-item>
                    @if ($quotation->valid_until)
                        <x-entity.definition-item :label="__('Valid Until')">{{ $quotation->valid_until->format('M j, Y') }}</x-entity.definition-item>
                    @endif
                    <x-entity.definition-item :label="__('Created By')">{{ $quotation->creator?->name ?? '—' }}</x-entity.definition-item>
                </x-entity.definition-list>
            </x-entity.section>
            <x-ui.button :href="route('quotations.index')" variant="link" size="sm">← {{ __('Back to quotations') }}</x-ui.button>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
