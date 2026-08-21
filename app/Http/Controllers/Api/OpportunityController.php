<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiOpportunityRequest;
use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\StoreSalesActivityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Http\Requests\UpdateOpportunityStageRequest;
use App\Http\Resources\CrmActivityResource;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Services\CrmActivityService;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\OpportunityService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OpportunityController extends Controller
{
    public function __construct(
        protected MetadataQueryDefinitionService $metadataDefinitions,
        protected MetadataQueryService $metadataQueries,
        protected OpportunityService $opportunityService,
        protected CrmActivityService $activities,
    ) {}

    public function index(IndexApiOpportunityRequest $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $organization = $tenant->get();
        $query = Opportunity::query()->with(['assignee', 'customer']);

        if ($stage = $request->string('stage')->toString()) {
            $query->where('stage', $stage);
        }

        if ($source = $request->string('source')->toString()) {
            $query->where('source', $source);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('company', 'like', "%{$search}%");
                    });
            });
        }

        $metadataRequest = $this->metadataDefinitions->requestForApi(
            $organization->id,
            'opportunity',
            $request->all(),
        );
        $this->metadataQueries->applyForApi($query, $metadataRequest, $organization->id);

        if (! $metadataRequest->sort) {
            $query->latest();
        }

        return OpportunityResource::collection(
            $query->paginate($request->perPage())
        );
    }

    public function show(Request $request, Opportunity $opportunity): OpportunityResource
    {
        $this->authorize('view', $opportunity);

        $opportunity->load(['assignee', 'creator', 'customer', 'contacts.contact', 'products', 'activities']);

        return new OpportunityResource($opportunity);
    }

    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        $opportunity = $this->opportunityService->create($request->validated(), $request->user());

        return (new OpportunityResource($opportunity->load(['assignee', 'customer'])))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): OpportunityResource
    {
        $opportunity = $this->opportunityService->update($opportunity, $request->validated(), $request->user());

        return new OpportunityResource($opportunity->load(['assignee', 'customer']));
    }

    public function updateStage(UpdateOpportunityStageRequest $request, Opportunity $opportunity): OpportunityResource
    {
        $opportunity = $this->opportunityService->updateStage($opportunity, $request->validated(), $request->user());

        return new OpportunityResource($opportunity->load(['assignee', 'customer']));
    }

    public function storeActivity(StoreSalesActivityRequest $request, Opportunity $opportunity): JsonResponse
    {
        $activity = $this->activities->createForOpportunity($opportunity, $request->validated(), $request->user());

        return (new CrmActivityResource($activity))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }
}
