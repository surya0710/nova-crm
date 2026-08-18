@php
    $showGst = ((float) $document->cgst_amount + (float) $document->sgst_amount + (float) $document->igst_amount + (float) $document->utgst_amount) > 0;
@endphp
<dl class="mt-4 max-w-xs ms-auto space-y-2 text-sm">
    <div class="flex justify-between">
        <dt class="text-ink-muted">{{ __('Subtotal') }}</dt>
        <dd class="text-ink-heading">{{ number_format((float) $document->subtotal, 2) }} {{ $document->currency }}</dd>
    </div>
    @if ((float) $document->discount_amount > 0)
        <div class="flex justify-between">
            <dt class="text-ink-muted">{{ __('Discount') }}</dt>
            <dd class="text-ink-heading">-{{ number_format((float) $document->discount_amount, 2) }} {{ $document->currency }}</dd>
        </div>
    @endif
    @if ((float) $document->taxable_amount > 0)
        <div class="flex justify-between">
            <dt class="text-ink-muted">{{ __('Taxable amount') }}</dt>
            <dd class="text-ink-heading">{{ number_format((float) $document->taxable_amount, 2) }} {{ $document->currency }}</dd>
        </div>
    @endif
    @if ($showGst)
        @if ((float) $document->cgst_amount > 0)
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('CGST') }}</dt><dd class="text-ink-heading">{{ number_format((float) $document->cgst_amount, 2) }} {{ $document->currency }}</dd></div>
        @endif
        @if ((float) $document->sgst_amount > 0)
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('SGST') }}</dt><dd class="text-ink-heading">{{ number_format((float) $document->sgst_amount, 2) }} {{ $document->currency }}</dd></div>
        @endif
        @if ((float) $document->igst_amount > 0)
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('IGST') }}</dt><dd class="text-ink-heading">{{ number_format((float) $document->igst_amount, 2) }} {{ $document->currency }}</dd></div>
        @endif
        @if ((float) $document->utgst_amount > 0)
            <div class="flex justify-between"><dt class="text-ink-muted">{{ __('UTGST') }}</dt><dd class="text-ink-heading">{{ number_format((float) $document->utgst_amount, 2) }} {{ $document->currency }}</dd></div>
        @endif
    @elseif ((float) $document->tax_total > 0)
        <div class="flex justify-between">
            <dt class="text-ink-muted">{{ __('Tax') }}</dt>
            <dd class="text-ink-heading">{{ number_format((float) $document->tax_total, 2) }} {{ $document->currency }}</dd>
        </div>
    @endif
    @if ((float) $document->cess_amount > 0)
        <div class="flex justify-between">
            <dt class="text-ink-muted">{{ __('Cess') }}</dt>
            <dd class="text-ink-heading">{{ number_format((float) $document->cess_amount, 2) }} {{ $document->currency }}</dd>
        </div>
    @endif
    @if ((float) $document->shipping_amount > 0)
        <div class="flex justify-between">
            <dt class="text-ink-muted">{{ __('Shipping / other') }}</dt>
            <dd class="text-ink-heading">{{ number_format((float) $document->shipping_amount, 2) }} {{ $document->currency }}</dd>
        </div>
    @endif
    <div class="flex justify-between border-t border-line pt-2">
        <dt class="font-semibold text-ink-heading">{{ __('Grand total') }}</dt>
        <dd class="font-bold text-ink-heading">{{ $document->formatted_total }}</dd>
    </div>
    {{ $slot ?? '' }}
</dl>
