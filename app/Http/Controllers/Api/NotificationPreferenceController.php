<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationPreferenceRequest;
use App\Http\Resources\NotificationPreferenceResource;
use App\Services\NotificationPreferenceService;
use App\Services\TenantContext;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function __construct(protected NotificationPreferenceService $preferenceService) {}

    public function show(Request $request, TenantContext $tenant): NotificationPreferenceResource
    {
        abort_unless($request->user()?->hasPermission('projects.notifications.manage'), 403);

        $preference = $this->preferenceService->getOrCreate($request->user(), $tenant->id());
        $this->authorize('view', $preference);

        return new NotificationPreferenceResource($preference);
    }

    public function update(
        UpdateNotificationPreferenceRequest $request,
        TenantContext $tenant,
    ): NotificationPreferenceResource {
        $preference = $this->preferenceService->update(
            $request->user(),
            $request->validated(),
            $tenant->id(),
            $request->user(),
        );

        $this->authorize('update', $preference);

        return new NotificationPreferenceResource($preference);
    }
}
