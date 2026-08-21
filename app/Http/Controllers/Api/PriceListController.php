<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiPriceListRequest;
use App\Http\Requests\StorePriceListRequest;
use App\Http\Requests\UpdatePriceListRequest;
use App\Http\Resources\PriceListResource;
use App\Models\PriceList;
use App\Services\PriceListService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PriceListController extends Controller
{
    public function __construct(protected PriceListService $priceLists) {}

    public function index(IndexApiPriceListRequest $request): AnonymousResourceCollection
    {
        $query = PriceList::query()->withCount('items');

        if ($search = $request->validated('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        return PriceListResource::collection(
            $query->latest()->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function show(PriceList $priceList): PriceListResource
    {
        $this->authorize('view', $priceList);

        return new PriceListResource($priceList->load(['items.product', 'customers']));
    }

    public function store(StorePriceListRequest $request, TenantContext $tenant): JsonResponse
    {
        $list = $this->priceLists->create($tenant->get(), $request->validated(), $request->user())
            ->load(['items.product', 'customers']);

        return (new PriceListResource($list))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePriceListRequest $request, PriceList $priceList): PriceListResource
    {
        $list = $this->priceLists->update($priceList, $request->validated(), $request->user())
            ->load(['items.product', 'customers']);

        return new PriceListResource($list);
    }

    public function destroy(PriceList $priceList): JsonResponse
    {
        $this->authorize('delete', $priceList);

        $this->priceLists->delete($priceList);

        return response()->json(['success' => true]);
    }
}
