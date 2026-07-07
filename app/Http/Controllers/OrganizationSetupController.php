<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Models\Organization;
use App\Services\OrganizationLogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrganizationSetupController extends Controller
{
    public function __construct(protected OrganizationLogoService $logoService) {}

    public function create(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasOrganizations()) {
            return redirect()->route('dashboard');
        }

        return view('organizations.setup', [
            'timezones' => timezone_identifiers_list(),
            'currencies' => config('nova.currencies'),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasOrganizations()) {
            return redirect()->route('dashboard');
        }

        $organization = Organization::create($request->safe()->except('logo'));

        if ($request->hasFile('logo')) {
            $organization->update([
                'logo' => $this->logoService->store($organization, $request->file('logo')),
            ]);
        }

        $ownerRole = $organization->roles()->where('slug', 'organization-owner')->firstOrFail();

        $organization->users()->attach($user->id, [
            'role_id' => $ownerRole->id,
            'role' => 'organization-owner',
            'is_owner' => true,
        ]);

        $request->session()->put('current_organization_id', $organization->id);

        return redirect()
            ->route('dashboard')
            ->with('status', 'organization-created');
    }
}
