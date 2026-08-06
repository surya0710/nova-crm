<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesClientEmailFields;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class SendPaymentMailRequest extends FormRequest
{
    use ValidatesClientEmailFields;

    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $payment && ($this->user()?->can('create', Payment::class) ?? false);
    }

    public function rules(): array
    {
        return $this->clientEmailRules();
    }
}
