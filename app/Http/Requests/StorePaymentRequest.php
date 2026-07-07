<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Payment::class) ?? false;
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'invoice_id' => [
                'required',
                'integer',
                Rule::exists('invoices', 'id')->where('organization_id', $organization?->id),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'payment_date' => ['required', 'date'],
            'method' => ['required', 'string', Rule::in(array_keys(config('payments.methods')))],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
