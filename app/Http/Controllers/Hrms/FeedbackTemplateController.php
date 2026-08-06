<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\CreateFeedbackTemplateRequest;
use App\Models\FeedbackTemplate;
use App\Services\Hrms\FeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeedbackTemplateController extends Controller
{
    public function __construct(protected FeedbackService $service)
    {
        $this->authorizeResource(FeedbackTemplate::class, 'template');
    }

    public function index(): View
    {
        return view('hrms.performance.feedback.templates.index', [
            'templates' => FeedbackTemplate::query()->withCount('questions')->latest()->paginate(20),
        ]);
    }

    public function store(CreateFeedbackTemplateRequest $request): RedirectResponse
    {
        $template = $this->service->createTemplate($request->validated(), $request->user());

        return redirect()->route('hrms.performance.feedback.templates.index')
            ->with('status', 'hrms-feedback-template-created');
    }

    public function show(FeedbackTemplate $template): View
    {
        $template->load('questions.competency');

        return view('hrms.performance.feedback.templates.show', [
            'template' => $template,
            'questionTypes' => config('hrms.feedback_question_types', []),
        ]);
    }
}
