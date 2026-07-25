<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Services\Marketing\MarketingCampaignService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingCampaignController extends Controller
{
    public function __construct(protected MarketingCampaignService $campaigns) {}

    public function index(Request $request, TenantContext $tenant): View
    {
        $this->authorizeView($request);
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $campaigns = $this->campaigns->list($organization, $request->string('status')->toString() ?: null);

        return view('marketing.campaigns.index', [
            'campaigns' => $campaigns,
            'status' => $request->string('status')->toString() ?: null,
            'statuses' => MarketingCampaign::STATUSES,
        ]);
    }

    public function create(Request $request, TenantContext $tenant): View
    {
        $this->authorizeManage($request);

        return view('marketing.campaigns.create', [
            'campaign' => new MarketingCampaign([
                'status' => MarketingCampaign::STATUS_DRAFT,
                'budget_currency' => $tenant->get()?->currency ?? 'USD',
            ]),
            'statuses' => MarketingCampaign::STATUSES,
            'channelOptions' => $this->channelOptions(),
        ]);
    }

    public function store(Request $request, TenantContext $tenant): RedirectResponse
    {
        $this->authorizeManage($request);
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $data = $this->validated($request);
        $campaign = $this->campaigns->create($organization, $request->user(), $data);

        return redirect()
            ->route('marketing.campaigns.show', $campaign)
            ->with('success', __('Campaign created.'));
    }

    public function show(Request $request, MarketingCampaign $campaign): View
    {
        $this->authorizeView($request);

        return view('marketing.campaigns.show', [
            'campaign' => $campaign->load('creator'),
            'performance' => $this->campaigns->performance($campaign),
        ]);
    }

    public function edit(Request $request, MarketingCampaign $campaign): View
    {
        $this->authorizeManage($request);

        return view('marketing.campaigns.edit', [
            'campaign' => $campaign,
            'statuses' => MarketingCampaign::STATUSES,
            'channelOptions' => $this->channelOptions(),
        ]);
    }

    public function update(Request $request, MarketingCampaign $campaign): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->campaigns->update($campaign, $this->validated($request, $campaign));

        return redirect()
            ->route('marketing.campaigns.show', $campaign)
            ->with('success', __('Campaign updated.'));
    }

    public function destroy(Request $request, MarketingCampaign $campaign): RedirectResponse
    {
        $this->authorizeManage($request);
        $campaign->delete();

        return redirect()
            ->route('marketing.campaigns.index')
            ->with('success', __('Campaign deleted.'));
    }

    /** @return array<string, mixed> */
    protected function validated(Request $request, ?MarketingCampaign $campaign = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'status' => ['required', Rule::in(MarketingCampaign::STATUSES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'budget_currency' => ['nullable', 'string', 'size:3'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', 'max:80'],
            'audience' => ['nullable', 'array'],
            'audience.segment' => ['nullable', 'string', 'max:180'],
            'audience.notes' => ['nullable', 'string', 'max:2000'],
            'utm_campaign' => ['nullable', 'string', 'max:180'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if (is_string($request->input('channels_text')) && trim((string) $request->input('channels_text')) !== '') {
            $extra = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('channels_text')))));
            $data['channels'] = array_values(array_unique(array_merge($data['channels'] ?? [], $extra)));
        }

        return $data;
    }

    /** @return list<string> */
    protected function channelOptions(): array
    {
        return ['paid_search', 'paid_social', 'organic', 'email', 'sms', 'referral', 'direct', 'other'];
    }

    protected function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyPermission([
                'marketing.view', 'marketing.manage', 'integrations.view', 'integrations.manage',
            ]),
            403
        );
    }

    protected function authorizeManage(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyPermission(['marketing.manage', 'integrations.manage']),
            403
        );
    }
}
