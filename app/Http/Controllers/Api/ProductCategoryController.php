<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ProductCategoryController extends Controller
{
    public function __construct(protected ProductCategoryService $categories) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductCategory::class);

        return ProductCategoryResource::collection(
            ProductCategory::query()
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(ApiQuery::perPage($request, 50))
        );
    }

    public function show(ProductCategory $productCategory): ProductCategoryResource
    {
        $this->authorize('view', $productCategory);

        $productCategory->loadCount('products');

        return new ProductCategoryResource($productCategory);
    }

    public function store(StoreProductCategoryRequest $request, TenantContext $tenant): JsonResponse
    {
        $category = $this->categories->create(
            $tenant->get(),
            $request->validated(),
            $request->user(),
        );

        return (new ProductCategoryResource($category))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): ProductCategoryResource
    {
        $category = $this->categories->update($productCategory, $request->validated());

        return new ProductCategoryResource($category);
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $this->authorize('delete', $productCategory);

        try {
            $this->categories->delete($productCategory);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
