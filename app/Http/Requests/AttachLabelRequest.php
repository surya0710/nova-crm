<?php

namespace App\Http\Requests;

use App\Models\ProjectLabel;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class AttachLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');
        $label = $this->route('label');

        if (! $task instanceof Task || ! $label instanceof ProjectLabel) {
            return false;
        }

        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->hasPermission('tasks.labels.manage', $task->organization)
            || $user->can('update', $task);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
