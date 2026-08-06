<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\CreateOfferTemplateRequest;
use App\Http\Requests\Recruitment\UpdateOfferTemplateRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\OfferTemplate;
use App\Services\Recruitment\OfferTemplateService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OfferTemplateController extends Controller
{
    public function __construct(protected OfferTemplateService $service)
    {
        $this->authorizeResource(OfferTemplate::class, 'offer_template');
    }

    public function index(): View
    {
        return view('hrms.recruitment.offer-templates.index', [
            'templates' => OfferTemplate::query()
                ->with(['department', 'designation'])
                ->latest()
                ->paginate(15),
            'departments' => Department::query()->orderBy('name')->get(),
            'designations' => Designation::query()->orderBy('name')->get(),
            'employmentTypes' => config('hrms.employment_types', []),
        ]);
    }

    public function show(OfferTemplate $offerTemplate): View
    {
        return view('hrms.recruitment.offer-templates.show', [
            'template' => $offerTemplate->load(['department', 'designation']),
            'employmentTypes' => config('hrms.employment_types', []),
        ]);
    }

    public function store(CreateOfferTemplateRequest $request): RedirectResponse
    {
        $org = app(TenantContext::class)->get();
        $data = array_merge($request->validated(), ['organization_id' => $org?->id]);

        $template = $this->service->createTemplate($data, $request->user());

        return redirect()->route('hrms.recruitment.offer-templates.show', $template)
            ->with('status', 'recruitment-offer-template-created');
    }

    public function update(UpdateOfferTemplateRequest $request, OfferTemplate $offerTemplate): RedirectResponse
    {
        $this->service->updateTemplate($offerTemplate, $request->validated(), $request->user());

        return redirect()->route('hrms.recruitment.offer-templates.show', $offerTemplate)
            ->with('status', 'recruitment-offer-template-updated');
    }

    public function destroy(OfferTemplate $offerTemplate): RedirectResponse
    {
        $this->service->deleteTemplate($offerTemplate, request()->user());

        return redirect()->route('hrms.recruitment.offer-templates.index')
            ->with('status', 'recruitment-offer-template-deleted');
    }
}
