@php
    $defaultItems = [[
        'product_id' => '',
        'sku' => '',
        'unit' => '',
        'hsn_sac' => '',
        'description' => '',
        'quantity' => 1,
        'unit_price' => 0,
        'tax_rate' => 0,
        'discount_percent' => 0,
        'cess_rate' => 0,
        'tax_inclusive' => false,
    ]];
    $hasItems = $invoice->exists || ($invoice->relationLoaded('items') && $invoice->items->isNotEmpty());
    $existingItems = $hasItems
        ? $invoice->items->map(fn ($item) => [
            'product_id' => $item->product_id ?? '',
            'sku' => $item->sku ?? '',
            'unit' => $item->unit ?? '',
            'hsn_sac' => $item->hsn_sac ?? '',
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_rate' => (float) $item->tax_rate,
            'discount_percent' => (float) $item->discount_percent,
            'cess_rate' => (float) ($item->cess_rate ?? 0),
            'tax_inclusive' => (bool) ($item->tax_inclusive ?? false),
        ])->values()->all()
        : $defaultItems;
    $initialItems = old('items', $existingItems);
    $productOptions = isset($productOptions) ? $productOptions : $products->map->catalogPayload()->values();
@endphp

<div class="space-y-8">
    @if (! empty($sourceSalesOrder))
        <div class="rounded-lg border border-primary-100 bg-primary-50/50 px-4 py-3 text-sm text-primary-800">
            {{ __('Creating invoice from :label :number', ['label' => strtolower(crm_term('sales_order')), 'number' => $sourceSalesOrder->number]) }}
        </div>
    @elseif (! empty($sourceQuotation))
        <div class="rounded-lg border border-primary-100 bg-primary-50/50 px-4 py-3 text-sm text-primary-800">
            {{ __('Creating invoice from :label :number', ['label' => strtolower(crm_term('quotation')), 'number' => $sourceQuotation->number]) }}
        </div>
    @endif

    <input type="hidden" name="quotation_id" value="{{ old('quotation_id', $invoice->quotation_id) }}">
    <input type="hidden" name="sales_order_id" value="{{ old('sales_order_id', $invoice->sales_order_id) }}">

    <x-forms.section :title="__(':label Details', ['label' => crm_term('invoice')])">
        <x-forms.field :label="crm_term('customer')" name="customer_id" required>
            <x-forms.select id="customer_id" name="customer_id" required>
                <option value="">{{ __('Select :label', ['label' => strtolower(crm_term('customer'))]) }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $invoice->customer_id) === (string) $customer->id)>{{ $customer->display_name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__(':label (optional)', ['label' => crm_term('deal')])" name="opportunity_id">
            <x-forms.select id="opportunity_id" name="opportunity_id">
                <option value="">{{ __('None') }}</option>
                @foreach ($opportunities as $opportunity)
                    <option value="{{ $opportunity->id }}" @selected((string) old('opportunity_id', $invoice->opportunity_id) === (string) $opportunity->id)>
                        {{ $opportunity->title }}@if($opportunity->customer) · {{ $opportunity->customer->display_name }}@endif
                    </option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Title')" name="title">
                <x-forms.input id="title" type="text" name="title" :value="old('title', $invoice->title)" />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('Issue Date')" name="issue_date" required>
            <x-forms.input id="issue_date" type="date" name="issue_date" :value="old('issue_date', $invoice->issue_date?->format('Y-m-d'))" required />
        </x-forms.field>
        <x-forms.field :label="__('Due Date')" name="due_date">
            <x-forms.input id="due_date" type="date" name="due_date" :value="old('due_date', $invoice->due_date?->format('Y-m-d'))" />
        </x-forms.field>
        <x-forms.field :label="__('Currency')" name="currency" required>
            <x-forms.select id="currency" name="currency" required>
                @foreach (config('invoices.currencies') as $value => $label)
                    <option value="{{ $value }}" @selected(old('currency', $invoice->currency) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div>
            @if ($invoice->exists)
                <p class="text-xs font-medium text-ink-muted">{{ __('Status') }}</p>
                <p class="mt-1 text-sm text-ink-heading">{{ $invoice->status_label }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('Status is managed from the invoice detail page.') }}</p>
            @else
                <input type="hidden" name="status" value="draft">
                <p class="text-xs font-medium text-ink-muted">{{ __('Status') }}</p>
                <p class="mt-1 text-sm text-ink-heading">{{ config('invoices.statuses.draft') }}</p>
            @endif
        </div>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Notes')" name="notes">
                <x-forms.textarea id="notes" name="notes" rows="3">{{ old('notes', $invoice->notes) }}</x-forms.textarea>
            </x-forms.field>
        </div>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Terms & conditions')" name="terms">
                <x-forms.textarea id="terms" name="terms" rows="3">{{ old('terms', $invoice->terms) }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>

    @include('commercial._line-items', [
        'initialItems' => $initialItems,
        'productOptions' => $productOptions,
        'customerTaxProfiles' => $customerTaxProfiles ?? [],
        'taxConfig' => $taxConfig ?? ['states' => [], 'seller_state_code' => null],
        'document' => $invoice,
    ])
</div>
