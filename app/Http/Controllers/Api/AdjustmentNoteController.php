<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdjustmentNoteRequest;
use App\Http\Requests\UpdateAdjustmentNoteRequest;
use App\Http\Resources\AdjustmentNoteResource;
use App\Models\AdjustmentNote;
use App\Services\AdjustmentNoteService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdjustmentNoteController extends Controller
{
    public function __construct(protected AdjustmentNoteService $notes) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AdjustmentNote::class);

        $query = AdjustmentNote::query()->with(['customer', 'items']);

        $type = $request->string('type')->toString() ?: $this->typeFromRoute($request);
        if ($type) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($customerId = $request->integer('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        return AdjustmentNoteResource::collection(
            $query->latest()->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function show(AdjustmentNote $adjustmentNote): AdjustmentNoteResource
    {
        $this->authorize('view', $adjustmentNote);

        return new AdjustmentNoteResource($adjustmentNote->load(['customer', 'items', 'invoice']));
    }

    public function store(StoreAdjustmentNoteRequest $request, TenantContext $tenant): JsonResponse
    {
        $type = $this->typeFromRoute($request) ?: ($request->string('type')->toString() ?: 'credit');
        $note = $this->notes->create($tenant->get(), $type, $request->validated(), $request->user())
            ->load(['customer', 'items']);

        return (new AdjustmentNoteResource($note))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAdjustmentNoteRequest $request, AdjustmentNote $adjustmentNote): AdjustmentNoteResource
    {
        $note = $this->notes->update($adjustmentNote, $request->validated(), $request->user())
            ->load(['customer', 'items']);

        return new AdjustmentNoteResource($note);
    }

    public function apply(AdjustmentNote $adjustmentNote, Request $request): AdjustmentNoteResource
    {
        $this->authorize('apply', $adjustmentNote);

        return new AdjustmentNoteResource(
            $this->notes->apply($adjustmentNote, $request->user())->load(['customer', 'invoice', 'items'])
        );
    }

    protected function typeFromRoute(Request $request): ?string
    {
        $name = (string) $request->route()?->getName();

        if (str_contains($name, 'credit-notes') || str_contains($request->path(), 'credit-notes')) {
            return 'credit';
        }

        if (str_contains($name, 'debit-notes') || str_contains($request->path(), 'debit-notes')) {
            return 'debit';
        }

        return null;
    }
}
