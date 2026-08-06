<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\OrganizationMemberService;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        $organization = app(TenantContext::class)->get();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($organization) {
                    if (! $organization) {
                        return;
                    }

                    $user = User::query()->where('email', strtolower(trim($value)))->first();

                    if ($user && $organization->users()->where('users.id', $user->id)->exists()) {
                        $fail(__('This user is already a member of the organization.'));
                    }
                },
            ],
            'role' => ['required', 'string', Rule::in(OrganizationMemberService::assignableRoleSlugs())],
            'send_invitation' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }

        if (! $this->has('send_invitation')) {
            $this->merge(['send_invitation' => true]);
        }
    }
}
