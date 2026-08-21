<?php

namespace App\Http\Requests;

use App\Models\Contact;
use App\Models\Customer;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            && ($this->user()?->can('create', [Contact::class, $customer]) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'is_primary' => ['sometimes', 'boolean'],
            'is_decision_maker' => ['sometimes', 'boolean'],
            'status' => ['required', 'string', Rule::in(array_keys(config('contacts.statuses')))],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_primary')) {
            $this->merge(['is_primary' => $this->boolean('is_primary')]);
        }
        if ($this->has('is_decision_maker')) {
            $this->merge(['is_decision_maker' => $this->boolean('is_decision_maker')]);
        }
    }
}
