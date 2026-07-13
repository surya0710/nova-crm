<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendOrganizationTestMailRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Mail\TestOrganizationMail;
use App\Services\MetadataEntityFormService;
use App\Services\OrganizationLogoService;
use App\Services\OrganizationMailConfig;
use App\Services\OrganizationMailer;
use App\Services\OrganizationTerminology;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function __construct(
        protected OrganizationLogoService $logoService,
        protected OrganizationMailer $organizationMailer,
        protected MetadataEntityFormService $metadataForms,
    ) {}

    public function edit(TenantContext $tenant): View
    {
        $organization = $tenant->get();

        abort_unless($organization, 404);
        $this->authorize('viewSettings', $organization);

        $mailConfig = app(OrganizationMailConfig::class)->for($organization);

        return view('organizations.edit', [
            'organization' => $organization,
            'roles' => $organization->roles()->with('permissions')->orderBy('name')->get(),
            'timezones' => timezone_identifiers_list(),
            'currencies' => config('nova.currencies'),
            'industries' => config('terminology.industries'),
            'terminologyKeys' => array_keys(config('terminology.defaults')),
            'terminologyLabels' => config('terminology.labels'),
            'currentTerms' => app(OrganizationTerminology::class)->all($organization),
            'industryPresets' => collect(config('terminology.industries'))
                ->map(fn ($industry, $key) => app(OrganizationTerminology::class)->presetForIndustry($key))
                ->all(),
            'mailSettings' => $mailConfig->toSettingsArray(),
            'mailDrivers' => config('organization_mail.drivers'),
            'mailEncryptions' => config('organization_mail.encryptions'),
            'metadataFields' => $this->metadataForms->fieldsFor($organization, 'organization', 'edit'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        abort_unless($organization, 404);
        $metadataValues = $this->metadataForms->validatedValuesFromRequest($organization, $organization, 'organization', 'edit', $request);

        $data = $request->safe()->except([
            'logo', 'remove_logo',
            'mail_enabled', 'mail_driver', 'mail_host', 'mail_port', 'mail_encryption',
            'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name',
        ]);

        if ($request->boolean('remove_logo')) {
            $this->logoService->delete($organization);
            $data['logo'] = null;
        }

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->logoService->store($organization, $request->file('logo'));
        }

        $settings = $organization->settings ?? [];

        if ($request->has('industry_type')) {
            $settings['industry_type'] = $request->validated('industry_type');
        }

        if ($request->has('terminology')) {
            $settings['terminology'] = collect($request->validated('terminology') ?? [])
                ->filter(fn ($value) => filled($value))
                ->all();
        }

        $settings['mail'] = OrganizationMailConfig::mergeSettings(
            $settings['mail'] ?? [],
            $request->all()
        );

        $data['settings'] = $settings;

        $organization->update($data);
        $this->metadataForms->persistValidatedValues($organization, $metadataValues);

        return redirect()
            ->route('organization.edit')
            ->with('status', 'organization-updated');
    }

    public function sendTestMail(SendOrganizationTestMailRequest $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        if (! $this->organizationMailer->isConfigured($organization)) {
            return redirect()
                ->route('organization.edit')
                ->with('error', __('Save and enable organization email settings before sending a test.'));
        }

        try {
            $this->organizationMailer->send(
                $organization,
                $request->validated('test_email'),
                new TestOrganizationMail($organization),
            );
        } catch (\Throwable $e) {
            return redirect()
                ->route('organization.edit')
                ->with('error', __('Test email failed: :message', ['message' => $e->getMessage()]));
        }

        return redirect()
            ->route('organization.edit')
            ->with('status', 'organization-mail-test-sent');
    }

}
