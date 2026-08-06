<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DuplicateLeadException;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiLeadRequest;
use App\Http\Requests\StoreApiLeadRequest;
use App\Http\Requests\UpdateApiLeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Services\LeadService;
use App\Services\LeadVisibilityService;
use App\Services\MetadataEntityFormService;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected LeadVisibilityService $leadVisibility,
        protected MetadataEntityFormService $metadataForms,
        protected MetadataQueryDefinitionService $metadataDefinitions,
        protected MetadataQueryService $metadataQueries,
    ) {}

    public function index(IndexApiLeadRequest $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $organization = $tenant->get();
        $query = $this->leadVisibility->visibleQuery($request->user(), $organization)->with('assignee');

        $this->leadService->searchQuery($query, $request->validated('search'));
        $this->leadService->geographicFilterQuery(
            $query,
            $request->validated('state'),
            $request->validated('country'),
        );

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $metadataRequest = $this->metadataDefinitions->requestForApi(
            $organization->id,
            'lead',
            $request->all(),
        );
        $this->metadataQueries->applyForApi($query, $metadataRequest, $organization->id);

        if (! $metadataRequest->sort) {
            $query->latest();
        }

        return LeadResource::collection(
            $query->paginate($request->perPage())->withQueryString()
        );
    }

    public function show(Request $request, Lead $lead): LeadResource
    {
        $this->authorize('view', $lead);

        $lead->load(['assignee', 'creator']);

        return new LeadResource($lead);
    }

    public function update(UpdateApiLeadRequest $request, Lead $lead, TenantContext $tenant): LeadResource
    {
        $data = $request->validated();
        $customFields = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        $metadataValues = $request->has('custom_fields')
            ? $this->metadataForms->validatedValues(
                $lead,
                $tenant->get(),
                'lead',
                $customFields,
                allowUnknown: true,
                context: 'edit',
            )
            : [];

        $lead = $this->leadService->update($lead, $data, $request->user(), $metadataValues);
        $lead->load(['assignee', 'creator']);

        return new LeadResource($lead);
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
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
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
            'data' => (new LeadResource($lead->load(['assignee', 'creator'])))->resolve($request),
            'message' => __('Lead created successfully.'),
        ], 201);
    }
}
