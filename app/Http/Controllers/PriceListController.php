<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePriceListRequest;
use App\Http\Requests\UpdatePriceListRequest;
use App\Models\Customer;
use App\Models\DiscountRule;
use App\Models\PriceList;
use App\Models\Product;
use App\Services\PriceListService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceListController extends Controller
{
    public function __construct(protected PriceListService $priceLists)
    {
        $this->authorizeResource(PriceList::class, 'price_list');
    }

    public function index(TenantContext $tenant): View
    {
        return view('price-lists.index', [
            'priceLists' => PriceList::query()->withCount('items')->latest()->paginate(15),
            'organization' => $tenant->get(),
        ]);
    }

    public function create(TenantContext $tenant): View
    {
        return view('price-lists.create', [
            'priceList' => new PriceList([
                'status' => 'active',
                'currency' => $tenant->get()?->currency ?? 'USD',
            ]),
            ...$this->formData(),
        ]);
    }

    public function store(StorePriceListRequest $request, TenantContext $tenant): RedirectResponse
    {
        $list = $this->priceLists->create($tenant->get(), $request->validated(), $request->user());

        return redirect()
            ->route('price-lists.show', $list)
            ->with('status', 'price-list-created');
    }

    public function show(PriceList $priceList): View
    {
        $priceList->load(['items.product', 'customers', 'creator']);

        $history = $priceList->items->isEmpty()
            ? collect()
            : \App\Models\ProductPriceHistory::query()
                ->where('price_list_id', $priceList->id)
                ->with(['product', 'changer'])
                ->latest()
                ->limit(50)
                ->get();

        return view('price-lists.show', [
            'priceList' => $priceList,
            'history' => $history,
            'discountRules' => DiscountRule::query()
                ->where('organization_id', $priceList->organization_id)
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function edit(PriceList $priceList): View
    {
        $priceList->load(['items', 'customers']);

        return view('price-lists.edit', [
            'priceList' => $priceList,
            ...$this->formData(),
        ]);
    }

    public function update(UpdatePriceListRequest $request, PriceList $priceList): RedirectResponse
    {
        $this->priceLists->update($priceList, $request->validated(), $request->user());

        return redirect()
            ->route('price-lists.show', $priceList)
            ->with('status', 'price-list-updated');
    }

    public function destroy(PriceList $priceList): RedirectResponse
    {
        $this->priceLists->delete($priceList);

        return redirect()
            ->route('price-lists.index')
            ->with('status', 'price-list-deleted');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'products' => Product::query()->where('status', 'active')->orderBy('name')->get(),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'company']),
        ];
    }
}
