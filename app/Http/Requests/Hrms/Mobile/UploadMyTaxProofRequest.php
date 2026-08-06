<?php

namespace App\Http\Requests\Hrms\Mobile;

use App\Models\TaxProof;
use App\Services\Hrms\EssContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadMyTaxProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(EssContext::class)->requireEmployee($this->user());

        return $this->user()?->can('upload', TaxProof::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $orgId = session('current_organization_id');
        $maxKb = (int) config('hrms.mobile.uploads.tax_proof.max_kb', 10240);
        $mimes = implode(',', config('hrms.mobile.uploads.tax_proof.mimes', ['jpeg', 'jpg', 'png', 'pdf']));

        return [
            'tax_declaration_id' => [
                'required',
                'integer',
                Rule::exists('tax_declarations', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'category' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'claimed_amount' => ['required', 'numeric', 'min:0'],
            'tax_declaration_item_id' => [
                'nullable',
                'integer',
                Rule::exists('tax_declaration_items', 'id')->where(fn ($q) => $q->where('organization_id', $orgId)),
            ],
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ];
    }
}
