<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function __construct(
        protected MetadataQueryDefinitionService $metadataDefinitions,
        protected MetadataQueryService $metadataQueries,
    ) {}

    public function index(IndexApiCustomerRequest $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $organization = $tenant->get();
        $query = Customer::query()->with('assignee');

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $metadataRequest = $this->metadataDefinitions->requestForApi(
            $organization->id,
            'customer',
            $request->all(),
        );
        $this->metadataQueries->applyForApi($query, $metadataRequest, $organization->id);

        if (! $metadataRequest->sort) {
            $query->latest();
        }

        return CustomerResource::collection(
            $query->paginate($request->perPage())
        );
    }

    public function show(Request $request, Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);

        $customer->load(['assignee', 'creator']);

        return new CustomerResource($customer);
    }
}
