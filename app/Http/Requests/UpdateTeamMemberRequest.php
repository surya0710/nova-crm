<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\OrganizationMemberService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');

        return $member instanceof User && ($this->user()?->can('update', $member) ?? false);
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(OrganizationMemberService::assignableRoleSlugs())],
        ];
    }
}
