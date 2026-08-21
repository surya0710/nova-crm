<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'invoice_id' => $this->invoice_id,
            'customer_id' => $this->customer_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'payment_date' => $this->payment_date?->toDateString(),
            'method' => $this->method,
            'method_label' => $this->method_label,
            'reference' => $this->reference,
            'bank_name' => $this->bank_name,
            'bank_account_name' => $this->bank_account_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_ifsc' => $this->bank_ifsc,
            'notes' => $this->notes,
            'formatted_amount' => $this->formatted_amount,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->display_name,
            ]),
            'invoice' => $this->whenLoaded('invoice', fn () => [
                'id' => $this->invoice?->id,
                'number' => $this->invoice?->number,
                'status' => $this->invoice?->status,
                'total' => $this->invoice ? (float) $this->invoice->total : null,
                'amount_paid' => $this->invoice ? (float) $this->invoice->amount_paid : null,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
