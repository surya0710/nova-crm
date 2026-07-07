<?php

namespace App\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('attachments.create') ?? false;
    }

    public function rules(): array
    {
        $maxKb = (int) config('attachments.max_size_kb', 10240);
        $mimes = implode(',', config('attachments.allowed_mimes', ['pdf']));

        return [
            'attachable_type' => ['required', 'string', Rule::in(array_keys(config('attachments.attachable')))],
            'attachable_id' => ['required', 'integer', 'min:1'],
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ];
    }

    public function resolveAttachable(): Model
    {
        $class = config('attachments.attachable.'.$this->validated('attachable_type'));

        return $class::query()->findOrFail($this->validated('attachable_id'));
    }
}
