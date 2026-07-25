<?php

namespace App\Http\Controllers\Careers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Careers\UpdateCandidateProfileRequest;
use App\Models\Organization;
use App\Services\Recruitment\CandidateProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidateProfileController extends Controller
{
    public function __construct(protected CandidateProfileService $profileService) {}

    public function edit(Organization $organization): View
    {
        $account = auth('candidate')->user();

        return view('careers.profile.edit', [
            'organization' => $organization,
            'candidate' => $account->candidate,
        ]);
    }

    public function update(UpdateCandidateProfileRequest $request, Organization $organization): RedirectResponse
    {
        $account = auth('candidate')->user();
        $this->profileService->updateProfile(
            $account,
            $request->safe()->except(['profile_photo']),
            $request->file('profile_photo'),
        );

        return redirect()->route('careers.profile.edit', $organization)
            ->with('status', __('Profile updated successfully.'));
    }
}
