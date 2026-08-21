<?php

namespace App\Http\Controllers\OrganizationSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCrmEmailTemplateRequest;
use App\Http\Requests\UpdateCrmEmailTemplateRequest;
use App\Models\CrmEmailTemplate;
use App\Services\CrmEmailTemplateService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CrmEmailTemplateController extends Controller
{
    public function __construct(protected CrmEmailTemplateService $templates) {}

    public function index(TenantContext $tenant): View
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);
        $this->assertCanManage($organization);

        return view('organization-settings.email-templates', [
            'organization' => $organization,
            'templates' => CrmEmailTemplate::query()
                ->where('organization_id', $organization->id)
                ->latest()
                ->paginate(20),
            'categories' => $this->templates->categoriesFor($organization),
            'modules' => $this->templates->modulesFor($organization),
            'variables' => config('crm_email.variables', []),
            'categoryVariables' => config('crm_email.category_variables', []),
        ]);
    }

    public function store(StoreCrmEmailTemplateRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);
        $this->assertCanManage($organization);

        $this->templates->create($organization, $request->validated(), $request->user());

        return redirect()
            ->route('organization.settings.email-templates.index')
            ->with('status', 'crm-email-template-created');
    }

    public function update(UpdateCrmEmailTemplateRequest $request, CrmEmailTemplate $template, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);
        $this->assertCanManage($organization);
        abort_unless((int) $template->organization_id === (int) $organization->id, 404);

        $this->templates->update($template, $request->validated(), $request->user());

        return redirect()
            ->route('organization.settings.email-templates.index')
            ->with('status', 'crm-email-template-updated');
    }

    public function destroy(CrmEmailTemplate $template, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);
        $this->assertCanManage($organization);
        abort_unless((int) $template->organization_id === (int) $organization->id, 404);

        $template->delete();

        return redirect()
            ->route('organization.settings.email-templates.index')
            ->with('status', 'crm-email-template-deleted');
    }

    protected function assertCanManage($organization): void
    {
        $user = request()->user();
        abort_unless(
            $user
            && (
                $user->can('update', $organization)
                || $user->hasPermission('email_templates.manage', $organization)
            ),
            403
        );
    }
}
