<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Services\Administration\OrganizationBrandingService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function edit(Request $request, TenantContext $tenant, OrganizationBrandingService $branding): View
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        return view('administration.branding.edit', [
            'organization' => $organization,
            'branding' => $branding->branding($organization),
        ]);
    }

    public function update(Request $request, TenantContext $tenant, OrganizationBrandingService $branding): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $organization = $tenant->get();
        abort_unless($organization, 404);

        $validated = $request->validate([
            'primary_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'email_from_name' => ['nullable', 'string', 'max:120'],
            'email_header_text' => ['nullable', 'string', 'max:255'],
            'login_headline' => ['nullable', 'string', 'max:255'],
            'login_tagline' => ['nullable', 'string', 'max:255'],
            'document_footer' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $branding->update(
            $organization,
            $validated,
            $request->user(),
            $request->file('logo'),
            $request->boolean('remove_logo')
        );

        return redirect()
            ->route('administration.branding.edit')
            ->with('status', __('Branding updated.'));
    }
}
