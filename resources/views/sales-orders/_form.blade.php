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

    $hasItems = $salesOrder->exists || ($salesOrder->relationLoaded('items') && $salesOrder->items->isNotEmpty());
    $existingItems = $hasItems
        ? $salesOrder->items->map(fn ($item) => [
            'product_id' => $item->product_id ?? '',
            'sku' => $item->sku ?? $item->product?->sku ?? '',
            'unit' => $item->unit ?? $item->product?->unit ?? '',
            'hsn_sac' => $item->hsn_sac ?? $item->product?->hsn_sac ?? '',
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
    $productOptions = $productOptions ?? $products->map->catalogPayload()->values();
@endphp

<div class="space-y-8">
    @if (! empty($sourceQuotation))
        <div class="rounded-lg border border-primary-100 bg-primary-50/50 px-4 py-3 text-sm text-primary-800">
            {{ __('Creating sales order from :label :number', ['label' => strtolower(crm_term('quotation')), 'number' => $sourceQuotation->number]) }}
        </div>
        <input type="hidden" name="quotation_id" value="{{ old('quotation_id', $salesOrder->quotation_id) }}">
    @endif

    <x-forms.section :title="__('Order Details')">
        <x-forms.field :label="__('Customer')" name="customer_id" required>
            <x-forms.select id="customer_id" name="customer_id" required>
                <option value="">{{ __('Select customer') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $salesOrder->customer_id) === (string) $customer->id)>{{ $customer->display_name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Deal (optional)')" name="opportunity_id">
            <x-forms.select id="opportunity_id" name="opportunity_id">
                <option value="">{{ __('None') }}</option>
                @foreach ($opportunities as $opportunity)
                    <option value="{{ $opportunity->id }}" @selected((string) old('opportunity_id', $salesOrder->opportunity_id) === (string) $opportunity->id)>
                        {{ $opportunity->title }}@if($opportunity->customer) · {{ $opportunity->customer->display_name }}@endif
                    </option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Title')" name="title">
                <x-forms.input id="title" type="text" name="title" :value="old('title', $salesOrder->title)" placeholder="{{ __('Website redesign order') }}" />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('Order Date')" name="order_date" required>
            <x-forms.input id="order_date" type="date" name="order_date" :value="old('order_date', $salesOrder->order_date?->format('Y-m-d'))" required />
        </x-forms.field>
        <x-forms.field :label="__('Expected Delivery Date')" name="expected_delivery_date">
            <x-forms.input id="expected_delivery_date" type="date" name="expected_delivery_date" :value="old('expected_delivery_date', $salesOrder->expected_delivery_date?->format('Y-m-d'))" />
        </x-forms.field>
        <x-forms.field :label="__('Currency')" name="currency" required>
            <x-forms.select id="currency" name="currency" required>
                @foreach (config('sales_orders.currencies') as $value => $label)
                    <option value="{{ $value }}" @selected(old('currency', $salesOrder->currency) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div>
            @if ($salesOrder->exists)
                <p class="text-xs font-medium text-ink-muted">{{ __('Status') }}</p>
                <p class="mt-1 text-sm text-ink-heading">{{ $salesOrder->status_label }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('Status is managed from the sales order detail page.') }}</p>
            @else
                <input type="hidden" name="status" value="draft">
                <p class="text-xs font-medium text-ink-muted">{{ __('Status') }}</p>
                <p class="mt-1 text-sm text-ink-heading">{{ config('sales_orders.statuses.draft') }}</p>
            @endif
        </div>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Notes')" name="notes">
                <x-forms.textarea id="notes" name="notes" rows="3" placeholder="{{ __('Internal or customer notes') }}">{{ old('notes', $salesOrder->notes) }}</x-forms.textarea>
            </x-forms.field>
        </div>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Terms & conditions')" name="terms">
                <x-forms.textarea id="terms" name="terms" rows="3" placeholder="{{ __('Payment terms, delivery timeline…') }}">{{ old('terms', $salesOrder->terms) }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>

    @include('commercial._line-items', [
        'initialItems' => $initialItems,
        'productOptions' => $productOptions,
        'customerTaxProfiles' => $customerTaxProfiles ?? [],
        'taxConfig' => $taxConfig ?? ['states' => [], 'seller_state_code' => null],
        'document' => $salesOrder,
    ])
</div>
