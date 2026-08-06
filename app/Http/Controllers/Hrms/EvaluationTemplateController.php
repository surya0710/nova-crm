<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateEvaluationTemplateRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EvaluationTemplate;
use App\Services\Recruitment\EvaluationTemplateService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationTemplateController extends Controller
{
    public function __construct(protected EvaluationTemplateService $service)
    {
        $this->authorizeResource(EvaluationTemplate::class, 'evaluation_template');
    }

    public function index(): View
    {
        return view('hrms.recruitment.evaluation-templates.index', [
            'templates' => EvaluationTemplate::query()
                ->with(['department', 'designation'])
                ->latest()
                ->paginate(15),
            'departments' => Department::query()->orderBy('name')->get(),
            'designations' => Designation::query()->orderBy('name')->get(),
            'questionTypes' => config('hrms.recruitment.evaluation_question_types', []),
        ]);
    }

    public function show(EvaluationTemplate $evaluationTemplate): View
    {
        return view('hrms.recruitment.evaluation-templates.show', [
            'template' => $evaluationTemplate->load('sections.questions'),
            'questionTypes' => config('hrms.recruitment.evaluation_question_types', []),
        ]);
    }

    public function store(CreateEvaluationTemplateRequest $request): RedirectResponse
    {
        $org = app(TenantContext::class)->get();
        $data = array_merge($request->validated(), ['organization_id' => $org?->id]);

        $template = $this->service->createTemplate($data, $request->user());

        return redirect()->route('hrms.recruitment.evaluation-templates.show', $template)
            ->with('status', 'recruitment-evaluation-template-created');
    }

    public function destroy(EvaluationTemplate $evaluationTemplate): RedirectResponse
    {
        $this->service->deleteTemplate($evaluationTemplate, request()->user());

        return redirect()->route('hrms.recruitment.evaluation-templates.index')
            ->with('status', 'recruitment-evaluation-template-deleted');
    }
}
