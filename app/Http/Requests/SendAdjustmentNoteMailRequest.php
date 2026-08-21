<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesClientEmailFields;
use Illuminate\Foundation\Http\FormRequest;

class SendAdjustmentNoteMailRequest extends FormRequest
{
    use ValidatesClientEmailFields;

    public function authorize(): bool
    {
        $note = $this->route('adjustment_note');

        return $note && ($this->user()?->can('send', $note) ?? false);
    }

    public function rules(): array
    {
        return $this->clientEmailRules();
    }
}
