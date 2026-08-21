<?php

namespace App\Http\Requests;

use App\Models\CustomerTicket;
use App\Services\CustomerTicketService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCustomerTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', CustomerTicket::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_keys(config('customer_tickets.statuses')))],
            'priority' => ['nullable', 'string', Rule::in(array_keys(config('customer_tickets.priorities')))],
            'customer_id' => ['nullable', 'integer'],
            'contact_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            'overdue' => ['nullable', 'in:1,true'],
            'unassigned' => ['nullable', 'in:1,true'],
            'sort' => ['nullable', 'string', Rule::in(CustomerTicketService::SORTABLE)],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'saved_filter' => ['nullable', 'integer'],
            'view' => ['nullable', 'string', Rule::in(['all'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['search', 'status', 'priority', 'customer_id', 'contact_id', 'assigned_to', 'overdue', 'unassigned', 'sort', 'sort_direction'] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim($this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
