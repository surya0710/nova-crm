<?php

namespace App\Http\Requests;

class UpdateProjectMilestoneRequest extends StoreProjectMilestoneRequest
{
    public function authorize(): bool
    {
        $milestone = $this->route('milestone');

        return $milestone && ($this->user()?->can('update', $milestone) ?? false);
    }
}
