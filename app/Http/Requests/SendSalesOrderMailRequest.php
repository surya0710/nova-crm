<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesClientEmailFields;
use Illuminate\Foundation\Http\FormRequest;

class SendSalesOrderMailRequest extends FormRequest
{
    use ValidatesClientEmailFields;

    public function authorize(): bool
    {
        $salesOrder = $this->route('sales_order');

        return $salesOrder && ($this->user()?->can('send', $salesOrder) ?? false);
    }

    public function rules(): array
    {
        return $this->clientEmailRules();
    }
}
