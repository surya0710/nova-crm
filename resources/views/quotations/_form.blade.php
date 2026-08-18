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

    $existingItems = $quotation->exists
        ? $quotation->items->map(fn ($item) => [
            'product_id' => $item->product_id ?? '',
            'sku' => $item->sku ?? $item->product?->sku ?? '',
            'unit' => $item->unit ?? $item->product?->unit ?? '',
            'hsn_sac' => $item->hsn_sac ?? $item->product?->hsn_sac ?? '',
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_rate' => (float) $item->tax_rate,
            'discount_percent' => (float) $item->discount_percent,
            'cess_rate' => (float) $item->cess_rate,
            'tax_inclusive' => (bool) $item->tax_inclusive,
        ])->values()->all()
        : $defaultItems;

    $initialItems = old('items', $existingItems);
    $productOptions = $productOptions ?? $products->map->catalogPayload()->values();
@endphp

<div class="space-y-8">
    <x-forms.section :title="__('Quotation Details')">
        <x-forms.field :label="__('Customer')" name="customer_id" required>
            <x-forms.select id="customer_id" name="customer_id" required>
                <option value="">{{ __('Select customer') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $quotation->customer_id) === (string) $customer->id)>{{ $customer->display_name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Deal (optional)')" name="opportunity_id">
            <x-forms.select id="opportunity_id" name="opportunity_id">
                <option value="">{{ __('None') }}</option>
                @foreach ($opportunities as $opportunity)
                    <option value="{{ $opportunity->id }}" @selected((string) old('opportunity_id', $quotation->opportunity_id) === (string) $opportunity->id)>
                        {{ $opportunity->title }}@if($opportunity->customer) · {{ $opportunity->customer->display_name }}@endif
                    </option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Title')" name="title">
                <x-forms.input id="title" type="text" name="title" :value="old('title', $quotation->title)" placeholder="{{ __('Website redesign proposal') }}" />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('Issue Date')" name="issue_date" required>
            <x-forms.input id="issue_date" type="date" name="issue_date" :value="old('issue_date', $quotation->issue_date?->format('Y-m-d'))" required />
        </x-forms.field>
        <x-forms.field :label="__('Valid Until / Expiry')" name="valid_until">
            <x-forms.input id="valid_until" type="date" name="valid_until" :value="old('valid_until', $quotation->valid_until?->format('Y-m-d'))" />
        </x-forms.field>
        <x-forms.field :label="__('Currency')" name="currency" required>
            <x-forms.select id="currency" name="currency" required>
                @foreach (config('quotations.currencies') as $value => $label)
                    <option value="{{ $value }}" @selected(old('currency', $quotation->currency) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div>
            @if ($quotation->exists)
                <p class="text-xs font-medium text-ink-muted">{{ __('Status') }}</p>
                <p class="mt-1 text-sm text-ink-heading">{{ $quotation->status_label }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ __('Status is managed from the quotation detail page.') }}</p>
            @else
                <input type="hidden" name="status" value="draft">
                <p class="text-xs font-medium text-ink-muted">{{ __('Status') }}</p>
                <p class="mt-1 text-sm text-ink-heading">{{ config('quotations.statuses.draft') }}</p>
            @endif
        </div>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Notes')" name="notes">
                <x-forms.textarea id="notes" name="notes" rows="3" placeholder="{{ __('Internal or customer notes') }}">{{ old('notes', $quotation->notes) }}</x-forms.textarea>
            </x-forms.field>
        </div>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Terms & conditions')" name="terms">
                <x-forms.textarea id="terms" name="terms" rows="3" placeholder="{{ __('Payment terms, delivery timeline…') }}">{{ old('terms', $quotation->terms) }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>

    @include('commercial._line-items', [
        'initialItems' => $initialItems,
        'productOptions' => $productOptions,
        'customerTaxProfiles' => $customerTaxProfiles ?? [],
        'taxConfig' => $taxConfig ?? ['states' => [], 'seller_state_code' => null],
        'document' => $quotation,
    ])
</div>
