<?php

namespace App\Http\Requests;

use App\Models\TaskComment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $comment = $this->route('comment');

        return $comment instanceof TaskComment
            && ($this->user()?->can('update', $comment) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:10000'],
        ];
    }
}
