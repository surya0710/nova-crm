<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $salesOrder = $this->route('sales_order');

        return $salesOrder && ($this->user()?->can('changeStatus', $salesOrder) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(config('sales_orders.statuses')))],
        ];
    }
}
