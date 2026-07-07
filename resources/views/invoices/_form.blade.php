@php
    $defaultItems = [['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => 0, 'discount_percent' => 0]];
    $hasItems = $invoice->exists || ($invoice->relationLoaded('items') && $invoice->items->isNotEmpty());
    $existingItems = $hasItems
        ? $invoice->items->map(fn ($item) => [
            'product_id' => $item->product_id ?? '',
            'description' => $item->description,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'tax_rate' => (float) $item->tax_rate,
            'discount_percent' => (float) $item->discount_percent,
        ])->values()->all()
        : $defaultItems;
    $initialItems = old('items', $existingItems);
    $productOptions = $products->map(fn ($product) => [
        'id' => $product->id,
        'name' => $product->name,
        'unit_price' => (float) $product->unit_price,
        'tax_rate' => (float) $product->tax_rate,
    ])->values();
@endphp

<div class="space-y-8">
    @if (! empty($sourceQuotation))
        <div class="rounded-lg border border-indigo-100 bg-indigo-50/50 px-4 py-3 text-sm text-indigo-800">
            {{ __('Creating invoice from :label :number', ['label' => strtolower(crm_term('quotation')), 'number' => $sourceQuotation->number]) }}
        </div>
    @endif

    <input type="hidden" name="quotation_id" value="{{ old('quotation_id', $invoice->quotation_id) }}">

    <div>
        <h4 class="text-sm font-semibold text-slate-900 mb-4">{{ __(':label Details', ['label' => crm_term('invoice')]) }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="customer_id" :value="crm_term('customer')" />
                <select id="customer_id" name="customer_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    <option value="">{{ __('Select :label', ['label' => strtolower(crm_term('customer'))]) }}</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $invoice->customer_id) === (string) $customer->id)>{{ $customer->display_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="opportunity_id" :value="__(':label (optional)', ['label' => crm_term('deal')])" />
                <select id="opportunity_id" name="opportunity_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($opportunities as $opportunity)
                        <option value="{{ $opportunity->id }}" @selected((string) old('opportunity_id', $invoice->opportunity_id) === (string) $opportunity->id)>
                            {{ $opportunity->title }}@if($opportunity->customer) · {{ $opportunity->customer->display_name }}@endif
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('opportunity_id')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="title" :value="__('Title')" />
                <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', $invoice->title)" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="issue_date" :value="__('Issue Date')" />
                <x-text-input id="issue_date" class="block mt-1 w-full" type="date" name="issue_date" :value="old('issue_date', $invoice->issue_date?->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="due_date" :value="__('Due Date')" />
                <x-text-input id="due_date" class="block mt-1 w-full" type="date" name="due_date" :value="old('due_date', $invoice->due_date?->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="currency" :value="__('Currency')" />
                <select id="currency" name="currency" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach (config('invoices.currencies') as $value => $label)
                        <option value="{{ $value }}" @selected(old('currency', $invoice->currency) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach (config('invoices.statuses') as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $invoice->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="notes" :value="__('Notes')" />
                <textarea id="notes" name="notes" rows="3" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $invoice->notes) }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </div>
    </div>

    @include('invoices._line-items', ['initialItems' => $initialItems, 'productOptions' => $productOptions])
</div>
