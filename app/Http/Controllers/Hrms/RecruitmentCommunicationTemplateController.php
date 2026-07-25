<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\StoreRecruitmentCommunicationTemplateRequest;
use App\Http\Requests\Recruitment\UpdateRecruitmentCommunicationTemplateRequest;
use App\Models\RecruitmentCommunicationTemplate;
use App\Services\Recruitment\RecruitmentCommunicationService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RecruitmentCommunicationTemplateController extends Controller
{
    public function __construct(protected RecruitmentCommunicationService $communication)
    {
    }

    public function index(TenantContext $tenant): View
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.communication.manage', $organization), 403);

        return view('hrms.recruitment.integrations.communication-templates', [
            'templates' => RecruitmentCommunicationTemplate::query()
                ->where('organization_id', $organization->id)
                ->latest()
                ->paginate(20),
            'templateKeys' => $this->communication->availableTemplateKeys(),
            'variables' => $this->communication->availableVariables(),
            'channels' => config('recruitment.communication.channels', []),
        ]);
    }

    public function store(StoreRecruitmentCommunicationTemplateRequest $request, TenantContext $tenant): RedirectResponse
    {
        $user = $request->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.communication.manage', $organization), 403);

        $this->communication->createTemplate($organization, $request->validated(), $user);

        return back()->with('status', 'recruitment-communication-template-created');
    }

    public function update(
        UpdateRecruitmentCommunicationTemplateRequest $request,
        RecruitmentCommunicationTemplate $template,
        TenantContext $tenant,
    ): RedirectResponse {
        $user = $request->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.communication.manage', $organization), 403);
        abort_unless((int) $template->organization_id === (int) $organization->id, 404);

        $this->communication->updateTemplate($template, $request->validated(), $user);

        return back()->with('status', 'recruitment-communication-template-updated');
    }

    public function submit(RecruitmentCommunicationTemplate $template, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.communication.manage', $organization), 403);
        abort_unless((int) $template->organization_id === (int) $organization->id, 404);

        $this->communication->submitForApproval($template, $user);

        return back()->with('status', 'recruitment-communication-template-submitted');
    }

    public function approve(RecruitmentCommunicationTemplate $template, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.communication.manage', $organization), 403);
        abort_unless((int) $template->organization_id === (int) $organization->id, 404);

        $this->communication->approveTemplate($template, $user);

        return back()->with('status', 'recruitment-communication-template-approved');
    }

    public function deactivate(RecruitmentCommunicationTemplate $template, TenantContext $tenant): RedirectResponse
    {
        $user = request()->user();
        $organization = $tenant->get();
        abort_unless($user && $organization && $user->hasPermission('recruitment.communication.manage', $organization), 403);
        abort_unless((int) $template->organization_id === (int) $organization->id, 404);

        $this->communication->deactivateTemplate($template, $user);

        return back()->with('status', 'recruitment-communication-template-deactivated');
    }
}