<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiOpportunityRequest;
use App\Http\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OpportunityController extends Controller
{
    public function __construct(
        protected MetadataQueryDefinitionService $metadataDefinitions,
        protected MetadataQueryService $metadataQueries,
    ) {}

    public function index(IndexApiOpportunityRequest $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $organization = $tenant->get();
        $query = Opportunity::query()->with(['assignee', 'customer']);

        if ($stage = $request->string('stage')->toString()) {
            $query->where('stage', $stage);
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

        $opportunity->load(['assignee', 'creator', 'customer']);

        return new OpportunityResource($opportunity);
    }
}
