<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('comment', $task) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $task = $this->route('task');

        return [
            'comment' => ['required', 'string', 'max:10000'],
            'parent_comment_id' => [
                'nullable',
                'integer',
                Rule::exists('task_comments', 'id')->where('task_id', $task?->id),
            ],
        ];
    }
}
