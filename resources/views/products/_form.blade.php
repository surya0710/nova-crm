<div class="space-y-8">
    <x-forms.section :title="__('Product Details')">
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Name')" name="name" required>
                <x-forms.input id="name" type="text" name="name" :value="old('name', $product->name)" required />
            </x-forms.field>
        </div>
        <x-forms.field :label="__('SKU / Item code')" name="sku">
            <x-forms.input id="sku" type="text" name="sku" :value="old('sku', $product->sku)" placeholder="SKU-001" />
        </x-forms.field>
        <x-forms.field :label="__('Category')" name="product_category_id">
            <x-forms.select id="product_category_id" name="product_category_id">
                <option value="">{{ __('Uncategorized') }}</option>
                @foreach ($categories ?? [] as $category)
                    <option value="{{ $category->id }}" @selected((string) old('product_category_id', $product->product_category_id) === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Type')" name="type" required>
            <x-forms.select id="type" name="type" required>
                @foreach (config('products.types') as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $product->type) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Status')" name="status" required>
            <x-forms.select id="status" name="status" required>
                @foreach (config('products.statuses') as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $product->status) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <div class="sm:col-span-2">
            <x-forms.field :label="__('Description')" name="description">
                <x-forms.textarea id="description" name="description" rows="3">{{ old('description', $product->description) }}</x-forms.textarea>
            </x-forms.field>
        </div>
    </x-forms.section>

    <x-forms.section :title="__('Pricing, Tax & Codes')">
        <x-forms.field :label="__('Unit of measure')" name="unit">
            <x-forms.select id="unit" name="unit">
                <option value="">{{ __('None') }}</option>
                @foreach (config('products.units') as $value => $label)
                    <option value="{{ $value }}" @selected(old('unit', $product->unit) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Selling price')" name="unit_price" required>
            <x-forms.input id="unit_price" type="number" name="unit_price" step="0.01" min="0" :value="old('unit_price', $product->unit_price)" required />
        </x-forms.field>
        <x-forms.field :label="__('Purchase / cost price')" name="cost_price">
            <x-forms.input id="cost_price" type="number" name="cost_price" step="0.01" min="0" :value="old('cost_price', $product->cost_price)" />
        </x-forms.field>
        <x-forms.field :label="__('Currency')" name="currency" required>
            <x-forms.select id="currency" name="currency" required>
                @foreach (config('products.currencies') as $value => $label)
                    <option value="{{ $value }}" @selected(old('currency', $product->currency) === $value)>{{ $label }}</option>
                @endforeach
            </x-forms.select>
        </x-forms.field>
        <x-forms.field :label="__('Default discount (%)')" name="default_discount_percent">
            <x-forms.input id="default_discount_percent" type="number" name="default_discount_percent" step="0.01" min="0" max="100" :value="old('default_discount_percent', $product->default_discount_percent)" />
        </x-forms.field>
        <x-forms.field :label="__('Default tax rate (%)')" name="tax_rate">
            <x-forms.input id="tax_rate" type="number" name="tax_rate" step="0.01" min="0" max="100" :value="old('tax_rate', $product->tax_rate)" />
        </x-forms.field>
        <x-forms.field :label="__('Cess rate (%)')" name="cess_rate">
            <x-forms.input id="cess_rate" type="number" name="cess_rate" step="0.01" min="0" max="100" :value="old('cess_rate', $product->cess_rate)" />
        </x-forms.field>
        <x-forms.field :label="__('HSN / SAC')" name="hsn_sac">
            <x-forms.input id="hsn_sac" type="text" name="hsn_sac" :value="old('hsn_sac', $product->hsn_sac)" placeholder="9983 / 8471" />
        </x-forms.field>
        <div class="sm:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm text-ink">
                <input type="hidden" name="tax_inclusive" value="0">
                <input type="checkbox" name="tax_inclusive" value="1" @checked(old('tax_inclusive', $product->tax_inclusive)) class="rounded border-line text-primary-600 focus:ring-primary-500">
                {{ __('Selling price is tax-inclusive') }}
            </label>
        </div>
    </x-forms.section>

    @include('metadata-fields._runtime_form', [
        'metadataFields' => $metadataFields ?? collect(),
        'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
        'record' => $product,
    ])
</div>
