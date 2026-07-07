<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $query = Product::query()
            ->with('creator')
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($category = $request->string('category')->trim()->toString()) {
            $query->where('category', 'like', "%{$category}%");
        }

        return view('products.index', [
            'products' => $query->paginate(15)->withQueryString(),
            'organization' => $organization,
            'filters' => $request->only(['search', 'status', 'type', 'category']),
        ]);
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
            ]),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'product-created');
    }

    public function show(Product $product): View
    {
        $product->load('creator');

        return view('products.show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'product-updated');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('status', 'product-deleted');
    }
}
