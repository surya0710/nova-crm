<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexApiProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\MetadataEntityFormService;
use App\Services\ProductService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $products,
        protected MetadataEntityFormService $metadataForms,
    ) {}

    public function index(IndexApiProductRequest $request): AnonymousResourceCollection
    {
        $query = Product::query()->with('productCategory');
        $this->products->searchQuery($query, $request->validated('search'));

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->validated('type')) {
            $query->where('type', $type);
        }

        if ($categoryId = $request->integer('product_category_id')) {
            $query->where('product_category_id', $categoryId);
        }

        return ProductResource::collection(
            $query->latest()->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function show(Product $product): ProductResource
    {
        $this->authorize('view', $product);

        $product->load(['productCategory', 'creator']);

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request, TenantContext $tenant): JsonResponse
    {
        $data = $request->validated();
        $customFields = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        $metadataValues = $this->metadataForms->validatedValues(
            null,
            $tenant->get(),
            'product',
            $customFields,
            allowUnknown: true,
            context: 'create',
        );

        $product = $this->products->create($tenant->get(), $data, $request->user(), $metadataValues);

        return (new ProductResource($product))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product, TenantContext $tenant): ProductResource
    {
        $data = $request->validated();
        $customFields = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        $metadataValues = $request->has('custom_fields')
            ? $this->metadataForms->validatedValues(
                $product,
                $tenant->get(),
                'product',
                $customFields,
                allowUnknown: true,
                context: 'edit',
            )
            : [];

        $product = $this->products->update($product, $data, $metadataValues);

        return new ProductResource($product);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(['success' => true]);
    }
}
