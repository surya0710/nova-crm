@php
    $statusVariant = [
        'active' => 'success',
        'inactive' => 'neutral',
    ];
@endphp

<x-app-layout>
    <x-flash-messages />

    <x-layouts.entity-detail
        :title="$product->name"
        :subtitle="collect([$product->type_label, $product->sku])->filter()->implode(' · ')"
    >
        <x-slot:breadcrumbs>
            <x-nav.breadcrumbs :items="[
                ['label' => __('CRM'), 'href' => route('crm.home')],
                ['label' => crm_term('products'), 'href' => route('products.index')],
                ['label' => $product->name, 'current' => true],
            ]" />
        </x-slot:breadcrumbs>

        <x-slot:actions>
            @can('update', $product)
                <x-ui.button :href="route('products.edit', $product)" variant="secondary" size="sm">{{ __('Edit') }}</x-ui.button>
            @endcan
            @can('delete', $product)
                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('{{ __('Delete this product?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" size="sm">{{ __('Delete') }}</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>

        <x-slot:tabs>
            <x-ui.badge :variant="$statusVariant[$product->status] ?? 'neutral'">{{ $product->status_label }}</x-ui.badge>
        </x-slot:tabs>

        <x-entity.section :title="__('Product Details')">
            <x-entity.definition-list>
                <x-entity.definition-item :label="__('Name')">{{ $product->name }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('SKU / Item code')">{{ $product->sku ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Type')">{{ $product->type_label }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Category')">{{ $product->productCategory?->name ?? $product->category ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="$product->hsn_sac_label">{{ $product->hsn_sac ?? '—' }}</x-entity.definition-item>
                <x-entity.definition-item :label="__('Unit')">{{ $product->unit_label ?? '—' }}</x-entity.definition-item>
                @if ($product->description)
                    <x-entity.definition-item :label="__('Description')" :span="2">
                        <div class="whitespace-pre-line">{{ $product->description }}</div>
                    </x-entity.definition-item>
                @endif
            </x-entity.definition-list>
        </x-entity.section>

        @include('metadata-fields._runtime_detail', [
            'metadataFields' => $metadataFields ?? collect(),
            'metadataPresenter' => $metadataPresenter ?? app(\App\Services\MetadataFormValuePresenter::class),
            'record' => $product,
        ])

        <x-slot:aside>
            <x-entity.section :title="__('Pricing')">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Selling Price') }}</dt>
                        <dd class="mt-1 text-2xl font-bold text-ink-heading">{{ $product->formatted_price }}</dd>
                        @if ($product->unit_label)
                            <p class="mt-0.5 text-xs text-ink-muted">{{ __('Per :unit', ['unit' => strtolower($product->unit_label)]) }}</p>
                        @endif
                        @if ($product->tax_inclusive)
                            <p class="mt-0.5 text-xs text-ink-muted">{{ __('Tax inclusive') }}</p>
                        @endif
                    </div>
                    @if ($product->cost_price !== null)
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Cost Price') }}</dt>
                            <dd class="mt-1 text-sm text-ink-heading">{{ number_format((float) $product->cost_price, 2) }} {{ $product->currency }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Default Discount') }}</dt>
                        <dd class="mt-1 text-sm text-ink-heading">{{ number_format((float) $product->default_discount_percent, 2) }}%</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Tax Rate') }}</dt>
                        <dd class="mt-1 text-sm text-ink-heading">{{ number_format((float) $product->tax_rate, 2) }}%</dd>
                    </div>
                    @if ((float) $product->cess_rate > 0)
                        <div>
                            <dt class="text-xs text-ink-muted">{{ __('Cess') }}</dt>
                            <dd class="mt-1 text-sm text-ink-heading">{{ number_format((float) $product->cess_rate, 2) }}%</dd>
                        </div>
                    @endif
                </dl>
            </x-entity.section>

            <x-entity.section :title="__('Meta')">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Created By') }}</dt>
                        <dd class="text-ink-heading">{{ $product->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-ink-muted">{{ __('Created') }}</dt>
                        <dd class="text-ink-heading">{{ $product->created_at->format('M j, Y') }}</dd>
                    </div>
                </dl>
            </x-entity.section>

            <x-ui.button :href="route('products.index')" variant="link" size="sm">← {{ __('Back to products') }}</x-ui.button>
        </x-slot:aside>
    </x-layouts.entity-detail>
</x-app-layout>
