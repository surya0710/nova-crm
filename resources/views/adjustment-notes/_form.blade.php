@php
    $defaultItems = [[
        'product_id' => '', 'sku' => '', 'unit' => '', 'hsn_sac' => '', 'description' => '',
        'quantity' => 1, 'unit_price' => 0, 'tax_rate' => 0, 'discount_percent' => 0, 'cess_rate' => 0, 'tax_inclusive' => false,
    ]];
    $hasItems = $note->exists || ($note->relationLoaded('items') && $note->items->isNotEmpty());
    $existingItems = $hasItems
        ? $note->items->map(fn ($item) => [
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
    $productOptions = $productOptions ?? $products->map->catalogPayload()->values();
    $prefix = $note->type === 'debit' ? 'debit-notes' : 'credit-notes';
@endphp

<div class="space-y-8">
    @if (! empty($sourceInvoice))
        <div class="rounded-lg border border-primary-100 bg-primary-50/50 px-4 py-3 text-sm text-primary-800">
            {{ __('Creating from invoice :number. Historical invoice totals will not change when this note is applied.', ['number' => $sourceInvoice->number]) }}
        </div>
        <input type="hidden" name="invoice_id" value="{{ old('invoice_id', $note->invoice_id) }}">
    @endif

    <x-forms.section :title="__('Note details')">
        <x-forms.field :label="__('Customer')" name="customer_id" required>
            <x-forms.select id="customer_id" name="customer_id" required>
                <option value="">{{ __('Select customer') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $note->customer_id) === (string) $customer->id)>{{ $customer->display_name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        @if (empty($sourceInvoice))
            <x-forms.field :label="__('Invoice')" name="invoice_id">
                <x-forms.input id="invoice_id" type="number" name="invoice_id" :value="old('invoice_id', $note->invoice_id)" />
            </x-forms.field>
        @endif
        <x-forms.field :label="__('Reason')" name="reason">
            <x-forms.select id="reason" name="reason">
                <option value="">{{ __('Select reason') }}</option>
                @foreach (config('adjustment_notes.reasons') as $value => $label)
                    <option value="{{ $value }}" @selected(old('reason', $note->reason) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Reason detail')" name="reason_detail">
                <x-forms.textarea id="reason_detail" name="reason_detail" rows="2">{{ old('reason_detail', $note->reason_detail) }}</x-forms.textarea>
            </x-forms.field>
        </div>
        <x-forms.field :label="__('Issue date')" name="issue_date" required>
            <x-forms.input id="issue_date" type="date" name="issue_date" :value="old('issue_date', $note->issue_date?->format('Y-m-d'))" required />
        </x-forms.field>
        <x-forms.field :label="__('Currency')" name="currency" required>
            <x-forms.select id="currency" name="currency" required>
                @foreach (config('adjustment_notes.currencies') as $value => $label)
                    <option value="{{ $value }}" @selected(old('currency', $note->currency) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Title')" name="title">
                <x-forms.input id="title" name="title" :value="old('title', $note->title)" />
            </x-forms.field>
        </div>
        @unless($note->exists)
            <input type="hidden" name="status" value="draft">
        @endunless
    </x-forms.section>

    @include('commercial._line-items', [
        'initialItems' => $initialItems,
        'productOptions' => $productOptions,
        'customerTaxProfiles' => $customerTaxProfiles ?? [],
        'taxConfig' => $taxConfig ?? ['states' => [], 'seller_state_code' => null],
        'document' => $note,
    ])
</div>
