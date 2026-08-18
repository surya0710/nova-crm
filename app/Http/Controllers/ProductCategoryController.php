<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductCategoryRequest;
use App\Http\Requests\UpdateProductCategoryRequest;
use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function __construct(protected ProductCategoryService $categories)
    {
        $this->authorizeResource(ProductCategory::class, 'productCategory');
    }

    public function index(TenantContext $tenant): View
    {
        return view('product-categories.index', [
            'categories' => ProductCategory::query()
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(50),
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('product-categories.create', [
            'category' => new ProductCategory(['is_active' => true, 'sort_order' => 0]),
            'organization' => $tenant->get(),
        ]);
    }

    public function store(StoreProductCategoryRequest $request, TenantContext $tenant): RedirectResponse
    {
        $this->categories->create($tenant->get(), $request->validated(), $request->user());

        return redirect()
            ->route('product-categories.index')
            ->with('status', 'product-category-created');
    }

    public function edit(ProductCategory $productCategory): View
    {
        return view('product-categories.edit', [
            'category' => $productCategory,
        ]);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $this->categories->update($productCategory, $request->validated());

        return redirect()
            ->route('product-categories.index')
            ->with('status', 'product-category-updated');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        try {
            $this->categories->delete($productCategory);
        } catch (ValidationException $e) {
            return redirect()
                ->route('product-categories.index')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('product-categories.index')
            ->with('status', 'product-category-deleted');
    }
}
