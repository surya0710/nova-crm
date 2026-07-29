<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! config('attachments.task_attachments_enabled', true)) {
            return false;
        }

        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('attachments', $task) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mimes = implode(',', config('attachments.allowed_mimes', [
            'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp',
            'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip',
        ]));

        return [
            'file' => [
                'required',
                'file',
                'max:'.config('attachments.max_size_kb', 10240),
                'mimes:'.$mimes,
            ],
        ];
    }
}
