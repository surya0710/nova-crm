<?php

namespace App\Http\Controllers;

use App\Services\MetadataFieldBlueprintActivationService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;

class MetadataFieldBlueprintActivationController extends Controller
{
    public function __construct(
        protected MetadataFieldBlueprintActivationService $activationService,
    ) {}

    public function __invoke(TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        abort_unless(request()->user()?->hasPermission('metadata.manage', $organization), 403);

        $application = $organization->initialTemplateApplication();
        $summary = $this->activationService->activateCopiedBlueprints($organization, $application?->version);

        return redirect()
            ->route('metadata-fields.index')
            ->with('status', 'metadata-blueprints-activated')
            ->with('metadata_activation_summary', $summary);
    }
}
