<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\OrganizationOnboarding;
use App\Services\Platform\OnboardingWizardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OnboardingApiController extends Controller
{
    public function __construct(
        protected OnboardingWizardService $wizard,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $actor = auth('platform')->user();
        Gate::forUser($actor)->authorize('platform.organizations.manage');

        $onboarding = $this->wizard->start($actor, $request->input('prefill', []));

        return response()->json([
            'message' => __('Onboarding started.'),
            'onboarding' => $onboarding,
            'progress' => $this->wizard->progress($onboarding),
        ], 201);
    }

    public function show(OrganizationOnboarding $onboarding): JsonResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.view');

        if (! $onboarding->isTerminal()) {
            $onboarding = $this->wizard->resume($onboarding);
        }

        return response()->json([
            'onboarding' => $onboarding->load('organization:id,name,plan,status'),
            'progress' => $this->wizard->progress($onboarding),
        ]);
    }

    public function completeStep(Request $request, OrganizationOnboarding $onboarding): JsonResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $validated = $request->validate([
            'step' => ['required', 'string'],
            'skip' => ['sometimes', 'boolean'],
            'payload' => ['sometimes', 'array'],
        ]);

        $updated = $this->wizard->completeStep(
            $onboarding,
            $validated['step'],
            $validated['payload'] ?? [],
            (bool) ($validated['skip'] ?? false),
            auth('platform')->user(),
        );

        return response()->json([
            'message' => __('Step processed.'),
            'onboarding' => $updated,
            'progress' => $this->wizard->progress($updated),
        ]);
    }

    public function progress(OrganizationOnboarding $onboarding): JsonResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.view');

        return response()->json($this->wizard->progress($onboarding));
    }

    public function validation(OrganizationOnboarding $onboarding): JsonResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.view');

        return response()->json([
            'checklist' => $this->wizard->buildChecklist($onboarding),
            'progress' => $this->wizard->progress($onboarding),
        ]);
    }

    public function finish(OrganizationOnboarding $onboarding): JsonResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $finished = $this->wizard->finish($onboarding, auth('platform')->user());

        return response()->json([
            'message' => __('Onboarding completed.'),
            'onboarding' => $finished->load('organization'),
        ]);
    }
}
