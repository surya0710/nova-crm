<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\MetadataEntityFormService;
use App\Services\PriceResolutionService;
use App\Services\ProductService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $products,
        protected MetadataEntityFormService $metadataForms,
        protected PriceResolutionService $prices,
    ) {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Product::query()->with(['creator', 'productCategory']);

        $this->products->searchQuery($query, $request->string('search')->trim()->toString());

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($categoryId = $request->integer('product_category_id')) {
            $query->where('product_category_id', $categoryId);
        } elseif ($category = $request->string('category')->trim()->toString()) {
            $query->where('category', 'like', "%{$category}%");
        }

        $this->products->applySort(
            $query,
            $request->string('sort')->toString() ?: null,
            $request->string('direction')->toString() ?: 'asc',
        );

        return view('products.index', [
            'products' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'categories' => ProductCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'type', 'category', 'product_category_id', 'sort', 'direction']),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::query()->where('status', 'active');
        $this->products->searchQuery($query, $request->string('q')->trim()->toString());

        $customer = null;
        if ($customerId = $request->integer('customer_id')) {
            $customer = Customer::query()->find($customerId);
        }
        $quantity = max(0.01, (float) $request->input('quantity', 1));

        return response()->json(
            $query->orderBy('name')->limit(25)->get()->map(function (Product $product) use ($customer, $quantity) {
                $payload = $product->catalogPayload();
                $resolved = $this->prices->resolve($product, $customer, $quantity);
                $payload['unit_price'] = $resolved['unit_price'];
                $payload['default_discount_percent'] = $resolved['discount_percent'];
                $payload['tax_inclusive'] = $resolved['tax_inclusive'];
                $payload['price_source'] = $resolved['source'];
                $payload['price_list_id'] = $resolved['price_list_id'];

                return $payload;
            })->values()
        );
    }

    public function resolvePrice(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $customer = null;
        if ($customerId = $request->integer('customer_id')) {
            $customer = Customer::query()
                ->where('organization_id', $product->organization_id)
                ->find($customerId);
        }

        return response()->json(
            $this->prices->resolve($product, $customer, max(0.01, (float) $request->input('quantity', 1)))
        );
    }

    public function create(TenantContext $tenant): View
    {
        $organization = $tenant->get();

        return view('products.create', [
            'product' => new Product([
                'status' => 'active',
                'type' => 'product',
                'currency' => $organization?->currency ?? 'USD',
                'unit' => 'each',
                'tax_rate' => 0,
                'default_discount_percent' => 0,
                'cess_rate' => 0,
                'tax_inclusive' => false,
            ]),
            'categories' => ProductCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'metadataFields' => $this->metadataForms->fieldsFor($organization, 'product', 'create'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function store(StoreProductRequest $request, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest(null, $tenant->get(), 'product', 'create', $request);

        $product = $this->products->create(
            $tenant->get(),
            $request->validated(),
            $request->user(),
            $metadataValues,
        );

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'product-created');
    }

    public function show(Product $product, TenantContext $tenant): View
    {
        $product->load(['creator', 'productCategory']);

        return view('products.show', [
            'product' => $product,
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'product', 'detail'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function edit(Product $product, TenantContext $tenant): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => ProductCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'metadataFields' => $this->metadataForms->fieldsFor($tenant->get(), 'product', 'edit'),
            'metadataPresenter' => $this->metadataForms->presenter(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, TenantContext $tenant): RedirectResponse
    {
        $metadataValues = $this->metadataForms->validatedValuesFromRequest($product, $tenant->get(), 'product', 'edit', $request);

        $this->products->update($product, $request->validated(), $metadataValues, $request->user());

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'product-updated');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        return redirect()
            ->route('products.index')
            ->with('status', 'product-deleted');
    }
}
