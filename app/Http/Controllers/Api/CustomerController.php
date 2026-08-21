<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiCustomerRequest;
use App\Http\Requests\StoreApiCustomerRequest;
use App\Http\Requests\UpdateApiCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Services\MetadataEntityFormService;
use App\Services\MetadataQueryDefinitionService;
use App\Services\MetadataQueryService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
        protected MetadataEntityFormService $metadataForms,
        protected MetadataQueryDefinitionService $metadataDefinitions,
        protected MetadataQueryService $metadataQueries,
    ) {}

    public function index(IndexApiCustomerRequest $request, TenantContext $tenant): AnonymousResourceCollection
    {
        $organization = $tenant->get();
        $query = Customer::query()->with(['assignee', 'primaryContact']);
        $this->customerService->applyIndexFilters($query, $request->validated());

        $metadataRequest = $this->metadataDefinitions->requestForApi(
            $organization->id,
            'customer',
            $request->all(),
        );
        $this->metadataQueries->applyForApi($query, $metadataRequest, $organization->id);
        $this->customerService->applyIndexSort($query, $request->validated(), (bool) $metadataRequest->sort);

        return CustomerResource::collection(
            $query->paginate($request->perPage())->withQueryString()
        );
    }

    public function show(Request $request, Customer $customer): CustomerResource
    {
        $this->authorize('view', $customer);

        $customer->load(['assignee', 'creator', 'primaryContact']);

        return new CustomerResource($customer);
    }

    public function store(StoreApiCustomerRequest $request, TenantContext $tenant): JsonResponse
    {
        $data = $request->validated();
        $customFields = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        $metadataValues = $this->metadataForms->validatedValues(
            null,
            $tenant->get(),
            'customer',
            $customFields,
            allowUnknown: true,
            context: 'create',
        );

        $customer = $this->customerService->create($data, $request->user(), $metadataValues);
        $customer->load(['assignee', 'creator', 'primaryContact']);

        return (new CustomerResource($customer))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateApiCustomerRequest $request, Customer $customer, TenantContext $tenant): CustomerResource
    {
        $data = $request->validated();
        $customFields = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        $metadataValues = $request->has('custom_fields')
            ? $this->metadataForms->validatedValues(
                $customer,
                $tenant->get(),
                'customer',
                $customFields,
                allowUnknown: true,
                context: 'edit',
            )
            : [];

        $customer = $this->customerService->update($customer, $data, $request->user(), $metadataValues);
        $customer->load(['assignee', 'creator', 'primaryContact']);

        return new CustomerResource($customer);
    }
}
