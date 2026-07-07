<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOpportunityNoteRequest;
use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Http\Requests\UpdateOpportunityStageRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Opportunity::class, 'opportunity');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Opportunity::query()
            ->with(['customer', 'assignee', 'creator'])
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('company', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }

        if ($stage = $request->string('stage')->toString()) {
            $query->where('stage', $stage);
        }

        if ($customerId = $request->integer('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($assignedTo = $request->integer('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        return view('pipeline.index', [
            'opportunities' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'customers' => Customer::query()->orderBy('company')->orderBy('name')->get(),
            'assignees' => $this->organizationMembers($organization),
            'filters' => $request->only(['search', 'stage', 'customer_id', 'assigned_to']),
            'stageCounts' => Opportunity::query()
                ->selectRaw('stage, count(*) as total')
                ->groupBy('stage')
                ->pluck('total', 'stage'),
            'pipelineSummary' => [
                'open_count' => Opportunity::query()->whereIn('stage', config('pipeline.open_stages'))->count(),
                'open_value' => (float) Opportunity::query()
                    ->whereIn('stage', config('pipeline.open_stages'))
                    ->sum('amount'),
                'won_count' => Opportunity::query()->where('stage', 'closed_won')->count(),
                'lost_count' => Opportunity::query()->where('stage', 'closed_lost')->count(),
            ],
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('pipeline.create', [
            'opportunity' => new Opportunity([
                'stage' => 'qualification',
                'currency' => $tenant->get()?->currency ?? 'USD',
            ]),
            'customers' => Customer::query()->orderBy('company')->orderBy('name')->get(),
            'leads' => Lead::query()->whereNotIn('status', ['won', 'lost'])->orderBy('name')->get(),
            'assignees' => $this->organizationMembers($tenant->get()),
        ]);
    }

    public function store(StoreOpportunityRequest $request): RedirectResponse
    {
        $opportunity = Opportunity::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('pipeline.show', $opportunity)
            ->with('status', 'opportunity-created');
    }

    public function show(Opportunity $opportunity): View
    {
        $opportunity->load(['customer', 'lead', 'assignee', 'creator', 'notes.user', 'attachments.uploader', 'tasks.assignee']);

        return view('pipeline.show', [
            'opportunity' => $opportunity,
        ]);
    }

    public function edit(Opportunity $opportunity, TenantContext $tenant): View
    {
        return view('pipeline.edit', [
            'opportunity' => $opportunity,
            'customers' => Customer::query()->orderBy('company')->orderBy('name')->get(),
            'leads' => Lead::query()->orderBy('name')->get(),
            'assignees' => $this->organizationMembers($tenant->get()),
        ]);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $opportunity->update($request->validated());

        return redirect()
            ->route('pipeline.show', $opportunity)
            ->with('status', 'opportunity-updated');
    }

    public function updateStage(UpdateOpportunityStageRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $validated = $request->validated();
        $stage = $validated['stage'];

        $attributes = ['stage' => $stage];

        if ($stage === 'closed_won') {
            $attributes['won_at'] = $validated['won_at'];
            $attributes['lost_reason'] = null;
        } elseif ($stage === 'closed_lost') {
            $attributes['lost_reason'] = $validated['lost_reason'];
            $attributes['won_at'] = null;
        }

        $opportunity->update($attributes);

        $flashStatus = match ($stage) {
            'closed_won' => 'opportunity-won',
            'closed_lost' => 'opportunity-lost',
            default => 'opportunity-stage-updated',
        };

        return redirect()
            ->route('pipeline.show', $opportunity)
            ->with('status', $flashStatus);
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $opportunity->delete();

        return redirect()
            ->route('pipeline.index')
            ->with('status', 'opportunity-deleted');
    }

    public function storeNote(StoreOpportunityNoteRequest $request, Opportunity $opportunity): RedirectResponse
    {
        OpportunityNote::query()->create([
            'organization_id' => $opportunity->organization_id,
            'opportunity_id' => $opportunity->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()
            ->route('pipeline.show', $opportunity)
            ->with('status', 'opportunity-note-added');
    }

    protected function organizationMembers(?\App\Models\Organization $organization)
    {
        if (! $organization) {
            return collect();
        }

        return $organization->users()->orderBy('name')->get();
    }
}
