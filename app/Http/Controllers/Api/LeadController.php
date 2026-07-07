<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DuplicateLeadException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApiLeadRequest;
use App\Models\Lead;
use App\Services\LeadService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class LeadController extends Controller
{
    public function __construct(protected LeadService $leadService) {}

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

    public function store(StoreApiLeadRequest $request, TenantContext $tenant): JsonResponse
    {
        $organization = $tenant->get();

        if (! $organization) {
            return response()->json([
                'success' => false,
                'message' => __('Organization context is required.'),
            ], 422);
        }

        try {
            $lead = $this->leadService->createFromApi(
                $request->validated(),
                $request->user(),
                $organization,
            );
        } catch (DuplicateLeadException $e) {
            return response()->json([
                'success' => false,
                'lead_id' => $e->lead->id,
                'message' => $e->getMessage(),
            ], 409);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('An unexpected error occurred while creating the lead.'),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id,
            'message' => __('Lead created successfully.'),
        ], 201);
    }
}
