<?php

namespace App\Http\Requests\Hrms;

use App\Models\FeedbackRequest;
use Illuminate\Foundation\Http\FormRequest;

class SubmitFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        $request = $this->route('feedbackRequest');

        return $request instanceof FeedbackRequest
            && ($this->user()?->can('submit', $request) ?? false);
    }

    public function rules(): array
    {
        return [
            'responses' => ['required', 'array', 'min:1'],
            'responses.*.feedback_question_id' => ['required', 'integer'],
            'responses.*.rating' => ['nullable', 'numeric'],
            'responses.*.text_response' => ['nullable', 'string'],
        ];
    }
}
