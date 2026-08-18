<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'sku',
        'unit',
        'hsn_sac',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_percent',
        'line_subtotal',
        'discount_amount',
        'taxable_amount',
        'tax_amount',
        'cgst_rate',
        'cgst_amount',
        'sgst_rate',
        'sgst_amount',
        'igst_rate',
        'igst_amount',
        'utgst_rate',
        'utgst_amount',
        'cess_rate',
        'cess_amount',
        'tax_inclusive',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'cgst_rate' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_rate' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_rate' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'utgst_rate' => 'decimal:2',
            'utgst_amount' => 'decimal:2',
            'cess_rate' => 'decimal:2',
            'cess_amount' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'line_total' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
