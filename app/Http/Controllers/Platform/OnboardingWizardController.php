<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\IndustryTemplate;
use App\Models\OrganizationOnboarding;
use App\Services\Dashboard\ModuleSubscriptionService;
use App\Services\Platform\OnboardingWizardService;
use App\Services\Platform\PlatformProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OnboardingWizardController extends Controller
{
    public function __construct(
        protected OnboardingWizardService $wizard,
        protected ModuleSubscriptionService $modules,
        protected PlatformProviderService $providers,
    ) {}

    public function index(Request $request): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.view');

        $query = OrganizationOnboarding::query()
            ->with(['organization:id,name,plan', 'initiator:id,name,email'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return view('platform.onboarding.index', [
            'sessions' => $query->paginate(20)->withQueryString(),
            'summary' => $this->wizard->dashboardSummary(),
        ]);
    }

    public function store(): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $onboarding = $this->wizard->start(auth('platform')->user());

        return redirect()
            ->route('platform.onboarding.show', $onboarding)
            ->with('status', __('Onboarding wizard started. Complete each step to go live.'));
    }

    public function show(OrganizationOnboarding $onboarding): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.view');

        $onboarding->load(['organization', 'initiator']);
        if (! $onboarding->isTerminal()) {
            $onboarding = $this->wizard->resume($onboarding);
        }
        $progress = $this->wizard->progress($onboarding);
        $step = $onboarding->current_step;
        $stepData = $onboarding->step_data[$step] ?? [];

        return view('platform.onboarding.show', [
            'onboarding' => $onboarding->load(['organization', 'initiator']),
            'progress' => $progress,
            'step' => $step,
            'stepMeta' => config('onboarding.steps.'.$step, []),
            'stepData' => $stepData,
            'allStepData' => $onboarding->step_data ?? [],
            'plans' => config('platform.plans'),
            'timezones' => timezone_identifiers_list(),
            'currencies' => config('nova.currencies'),
            'selectableModules' => config('onboarding.selectable_modules', []),
            'moduleLabels' => collect(config('modules', []))
                ->filter(fn ($m) => is_array($m) && isset($m['name']))
                ->mapWithKeys(fn ($m, $k) => [$k => $m['name']])
                ->all(),
            'importEntities' => config('onboarding.import_entities', []),
            'providerHealth' => $this->providers->healthSummary(),
            'templates' => IndustryTemplate::query()
                ->with('currentVersion')
                ->where('status', 'published')
                ->whereNotNull('current_version_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'enabledModules' => $onboarding->organization
                ? $this->modules->enabledModules($onboarding->organization)
                : [],
        ]);
    }

    public function saveDraft(Request $request, OrganizationOnboarding $onboarding): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $step = $request->string('step', $onboarding->current_step)->toString();
        $this->wizard->saveDraft($onboarding, $step, $request->except(['_token', 'step', '_method']));

        return back()->with('status', __('Draft saved.'));
    }

    public function completeStep(Request $request, OrganizationOnboarding $onboarding): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $step = $request->string('step', $onboarding->current_step)->toString();
        $skip = $request->boolean('skip');

        $this->wizard->completeStep(
            $onboarding,
            $step,
            $request->except(['_token', 'step', 'skip', '_method']),
            $skip,
            auth('platform')->user(),
        );

        return redirect()
            ->route('platform.onboarding.show', $onboarding->fresh())
            ->with('status', $skip ? __('Step skipped.') : __('Step completed.'));
    }

    public function previous(Request $request, OrganizationOnboarding $onboarding): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $to = $request->string('to_step')->toString();
        $this->wizard->goBack($onboarding, $to);

        return redirect()->route('platform.onboarding.show', $onboarding->fresh());
    }

    public function finish(OrganizationOnboarding $onboarding): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.organizations.manage');

        $finished = $this->wizard->finish($onboarding, auth('platform')->user());

        return redirect()
            ->route('platform.organizations.show', $finished->organization)
            ->with('status', __('Onboarding completed. Organization is ready.'));
    }
}
