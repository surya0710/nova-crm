<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalesActivityRequest;
use App\Http\Resources\CrmActivityResource;
use App\Models\CrmActivity;
use App\Services\CrmActivityService;
use App\Services\TenantContext;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CrmActivityController extends Controller
{
    public function __construct(protected CrmActivityService $activities) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CrmActivity::class);

        $query = CrmActivity::query()->with(['customer', 'contact', 'opportunity', 'assignee']);
        $this->activities->applyIndexFilters($query, $request->all(), $request->user());

        return CrmActivityResource::collection(
            $query->latest('occurred_at')->paginate(ApiQuery::perPage($request))->withQueryString()
        );
    }

    public function store(StoreSalesActivityRequest $request, TenantContext $tenant): JsonResponse
    {
        $data = $request->validated();
        $data['organization_id'] = $tenant->get()?->id;
        $activity = $this->activities->create($data, $request->user());

        return (new CrmActivityResource($activity))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(201);
    }

    public function complete(Request $request, CrmActivity $crmActivity): CrmActivityResource
    {
        $this->authorize('update', $crmActivity);
        $activity = $this->activities->complete($crmActivity, $request->user());

        return new CrmActivityResource($activity);
    }
}
