<div class="space-y-8">
    <x-forms.section :title="__('Product Details')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Name')" name="name" required>
                <x-forms.input id="name" type="text" name="name" :value="old('name', $product->name)" required />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('SKU')" name="sku">
            <x-forms.input id="sku" type="text" name="sku" :value="old('sku', $product->sku)" placeholder="SKU-001" />
        </x-forms.field>
        <x-forms.field :label="__('Category')" name="category">
            <x-forms.input id="category" type="text" name="category" :value="old('category', $product->category)" placeholder="Software, Consulting…" />
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Description')" name="description">
                <x-forms.textarea id="description" name="description" rows="3">{{ old('description', $product->description) }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>

    <x-forms.section :title="__('Pricing & Tax')">
        <x-forms.field :label="__('Type')" name="type" required>
            <x-forms.select id="type" name="type" required>
                @foreach (config('products.types') as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $product->type) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Unit')" name="unit">
            <x-forms.select id="unit" name="unit">
                <option value="">{{ __('None') }}</option>
                @foreach (config('products.units') as $value => $label)
                    <option value="{{ $value }}" @selected(old('unit', $product->unit) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Unit Price')" name="unit_price" required>
            <x-forms.input id="unit_price" type="number" name="unit_price" step="0.01" min="0" :value="old('unit_price', $product->unit_price)" required />
        </x-forms.field>
        <x-forms.field :label="__('Currency')" name="currency" required>
            <x-forms.select id="currency" name="currency" required>
                @foreach (config('products.currencies') as $value => $label)
                    <option value="{{ $value }}" @selected(old('currency', $product->currency) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Tax Rate (%)')" name="tax_rate">
            <x-forms.input id="tax_rate" type="number" name="tax_rate" step="0.01" min="0" max="100" :value="old('tax_rate', $product->tax_rate)" />
        </x-forms.field>
        <x-forms.field :label="__('Status')" name="status" required>
            <x-forms.select id="status" name="status" required>
                @foreach (config('products.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $product->status) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
    </x-forms.section>
</div>
