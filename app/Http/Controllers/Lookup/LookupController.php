<?php

namespace App\Http\Controllers\Lookup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lookup\LookupSearchRequest;
use App\Services\Lookup\LookupPlatformService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    public function __construct(
        protected LookupPlatformService $lookups,
        protected TenantContext $tenant,
    ) {}

    public function search(LookupSearchRequest $request, string $entity): JsonResponse
    {
        $organization = $this->tenant->get();
        abort_unless($organization, 404);

        if ($request->filled('id')) {
            $item = $this->lookups->find($entity, $organization, $request->user(), (int) $request->input('id'));

            return response()->json([
                'data' => $item ? [$item] : [],
                'meta' => [
                    'page' => 1,
                    'per_page' => 1,
                    'total' => $item ? 1 : 0,
                    'has_more' => false,
                ],
            ]);
        }

        $validated = $request->validated();

        return response()->json(
            $this->lookups->search(
                $entity,
                $organization,
                $request->user(),
                (string) ($validated['q'] ?? ''),
                (int) ($validated['page'] ?? 1),
                (int) ($validated['per_page'] ?? 0),
            )
        );
    }
}
