<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\PublishIndustryTemplateRequest;
use App\Http\Requests\Platform\StoreIndustryTemplateRequest;
use App\Http\Requests\Platform\UpdateIndustryTemplateRequest;
use App\Models\IndustryTemplate;
use App\Models\IndustryTemplateVersion;
use App\Services\Platform\IndustryTemplatePayloadValidator;
use App\Services\Platform\IndustryTemplatePublishService;
use App\Services\Platform\IndustryTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class IndustryTemplateController extends Controller
{
    public function index(Request $request, IndustryTemplateService $service): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.industry_templates.view');

        return view('platform.industry-templates.index', [
            'templates' => $service->paginate($request->only(['search', 'status', 'visibility'])),
            'statuses' => config('industry_templates.statuses'),
            'visibilities' => config('industry_templates.visibility'),
            'filters' => $request->only(['search', 'status', 'visibility']),
        ]);
    }

    public function create(IndustryTemplatePayloadValidator $payloadValidator): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.industry_templates.create');

        return view('platform.industry-templates.create', [
            'template' => new IndustryTemplate([
                'visibility' => 'internal',
                'draft_payload' => $payloadValidator->defaultPayload(),
            ]),
            'visibilities' => config('industry_templates.visibility'),
        ]);
    }

    public function store(StoreIndustryTemplateRequest $request, IndustryTemplateService $service): RedirectResponse
    {
        $template = $service->create($request->validated(), auth('platform')->user());

        return redirect()
            ->route('platform.industry-templates.show', $template)
            ->with('status', __('Industry template created.'));
    }

    public function show(IndustryTemplate $industryTemplate): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.industry_templates.view');

        $industryTemplate->load([
            'currentVersion',
            'versions' => fn ($query) => $query->latest('version'),
            'creator',
            'publisher',
        ])->loadCount('applications');

        return view('platform.industry-templates.show', [
            'template' => $industryTemplate,
        ]);
    }

    public function edit(IndustryTemplate $industryTemplate): View
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.industry_templates.update');

        return view('platform.industry-templates.edit', [
            'template' => $industryTemplate,
            'visibilities' => config('industry_templates.visibility'),
        ]);
    }

    public function update(
        UpdateIndustryTemplateRequest $request,
        IndustryTemplate $industryTemplate,
        IndustryTemplateService $service,
    ): RedirectResponse {
        $service->updateDraft($industryTemplate, $request->validated(), auth('platform')->user());

        return redirect()
            ->route('platform.industry-templates.show', $industryTemplate)
            ->with('status', __('Industry template draft updated.'));
    }

    public function publish(
        PublishIndustryTemplateRequest $request,
        IndustryTemplate $industryTemplate,
        IndustryTemplatePublishService $service,
    ): RedirectResponse {
        $version = $service->publish($industryTemplate, auth('platform')->user(), $request->validated('changelog'));

        return redirect()
            ->route('platform.industry-templates.show', $industryTemplate)
            ->with('status', __('Industry template version :version published.', ['version' => $version->version]));
    }

    public function inactivate(IndustryTemplate $industryTemplate, IndustryTemplateService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.industry_templates.archive');

        $service->inactivate($industryTemplate, auth('platform')->user());

        return back()->with('status', __('Industry template inactivated.'));
    }

    public function archive(IndustryTemplate $industryTemplate, IndustryTemplateService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.industry_templates.archive');

        $service->archive($industryTemplate, auth('platform')->user());

        return back()->with('status', __('Industry template archived.'));
    }

    public function reactivate(IndustryTemplate $industryTemplate, IndustryTemplateService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.industry_templates.archive');

        $service->reactivate($industryTemplate, auth('platform')->user());

        return back()->with('status', __('Industry template reactivated.'));
    }

    public function clone(Request $request, IndustryTemplateVersion $version, IndustryTemplateService $service): RedirectResponse
    {
        Gate::forUser(auth('platform')->user())->authorize('platform.industry_templates.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $template = $service->cloneVersion($version->load('template'), $validated, auth('platform')->user());

        return redirect()
            ->route('platform.industry-templates.edit', $template)
            ->with('status', __('Industry template cloned into a new draft.'));
    }
}
