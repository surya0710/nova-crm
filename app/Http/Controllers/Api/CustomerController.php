<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->hasPermission('customers.view'), 403);

        $query = Customer::query()->with('assignee')->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        return \App\Http\Resources\CustomerResource::collection(
            $query->paginate($request->integer('per_page', 15))
        );
    }

    public function show(Request $request, Customer $customer): \App\Http\Resources\CustomerResource
    {
        $this->authorize('view', $customer);

        $customer->load(['assignee', 'creator']);

        return new \App\Http\Resources\CustomerResource($customer);
    }
}
