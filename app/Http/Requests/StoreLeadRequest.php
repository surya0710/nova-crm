<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Services\LeadFollowUpService;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lead::class) ?? false;
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['required', 'string', Rule::in(array_keys(config('leads.sources')))],
            'industry' => ['nullable', 'string', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'priority' => ['required', 'string', Rule::in(array_keys(config('leads.priorities')))],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('organization_user', 'user_id')->where('organization_id', $organization?->id),
            ],
            'status' => ['required', 'string', Rule::in(array_keys(config('leads.statuses')))],
            ...app(LeadFollowUpService::class)->validationRules(),
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('tags') && is_string($this->tags)) {
            $tags = trim($this->tags);

            $this->merge([
                'tags' => $tags === '' ? null : array_values(array_filter(array_map('trim', explode(',', $tags)))),
            ]);
        }

        if ($this->has('next_follow_up_at') && $this->input('next_follow_up_at') === '') {
            $this->merge([
                'next_follow_up_at' => null,
                'next_follow_up_note' => null,
            ]);
        }
    }
}
