<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasPermission('leads.view'), 403);

        $query = Lead::query()->with('assignee')->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return \App\Http\Resources\LeadResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(Request $request, Lead $lead): \App\Http\Resources\LeadResource
    {
        $this->authorize('view', $lead);

        $lead->load(['assignee', 'creator']);

        return new \App\Http\Resources\LeadResource($lead);
    }

    public function store(Request $request): \App\Http\Resources\LeadResource
    {
        $this->authorize('create', Lead::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'string', 'max:20'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $lead = Lead::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'new',
            'priority' => $validated['priority'] ?? 'medium',
            'created_by' => $request->user()->id,
        ]);

        return new \App\Http\Resources\LeadResource($lead);
    }
}
