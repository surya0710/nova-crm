<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadNoteRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadFollowUpRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Lead::class, 'lead');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Lead::query()
            ->with(['assignee', 'creator'])
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($source = $request->string('source')->toString()) {
            $query->where('source', $source);
        }

        if ($priority = $request->string('priority')->toString()) {
            $query->where('priority', $priority);
        }

        if ($assignedTo = $request->integer('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        return view('leads.index', [
            'leads' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'assignees' => $this->organizationMembers($organization),
            'filters' => $request->only(['search', 'status', 'source', 'priority', 'assigned_to']),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('leads.create', [
            'lead' => new Lead(['status' => 'new', 'priority' => 'medium', 'source' => 'manual_entry']),
            'assignees' => $this->organizationMembers($tenant->get()),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = Lead::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-created');
    }

    public function show(Lead $lead): View
    {
        $lead->load(['assignee', 'creator', 'notes.user', 'attachments.uploader', 'tasks.assignee']);

        return view('leads.show', [
            'lead' => $lead,
        ]);
    }

    public function edit(Lead $lead, TenantContext $tenant): View
    {
        return view('leads.edit', [
            'lead' => $lead,
            'assignees' => $this->organizationMembers($tenant->get()),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-updated');
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update(['status' => $request->validated('status')]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-status-updated');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()
            ->route('leads.index')
            ->with('status', 'lead-deleted');
    }

    public function storeNote(StoreLeadNoteRequest $request, Lead $lead): RedirectResponse
    {
        LeadNote::query()->create([
            'organization_id' => $lead->organization_id,
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-note-added');
    }

    public function dueFollowUps(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermission('leads.view'), 403);

        $leads = Lead::query()
            ->dueForFollowUpAlert()
            ->with(['assignee'])
            ->orderBy('next_follow_up_at')
            ->limit(10)
            ->get()
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => $lead->status_label,
                'priority' => $lead->priority_label,
                'assigned_to' => $lead->assignee?->name,
                'next_follow_up_at' => $lead->next_follow_up_at?->toIso8601String(),
                'next_follow_up_at_formatted' => $lead->next_follow_up_at?->format('M j, Y g:i A'),
                'next_follow_up_note' => $lead->next_follow_up_note,
                'url' => route('leads.show', $lead),
            ]);

        return response()->json(['data' => $leads]);
    }

    public function acknowledgeFollowUp(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        $lead->update(['follow_up_alerted_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function updateFollowUp(UpdateLeadFollowUpRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();

        $lead->update([
            'next_follow_up_at' => $validated['next_follow_up_at'] ?? null,
            'next_follow_up_note' => $validated['next_follow_up_note'] ?? null,
        ]);

        return redirect()
            ->route('leads.show', $lead)
            ->with('status', 'lead-follow-up-updated');
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function organizationMembers(?\App\Models\Organization $organization)
    {
        if (! $organization) {
            return collect();
        }

        return $organization->users()->orderBy('name')->get();
    }
}
