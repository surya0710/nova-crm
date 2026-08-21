<?php

namespace App\Http\Requests;

class UpdateSalesOrderRequest extends StoreSalesOrderRequest
{
    public function authorize(): bool
    {
        $salesOrder = $this->route('sales_order');

        return $salesOrder && ($this->user()?->can('update', $salesOrder) ?? false);
    }

    public function rules(): array
    {
        return $this->salesOrderFieldRules(isUpdate: true);
    }
}
