<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationPreferenceRequest;
use App\Services\NotificationPreferenceService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationPreferenceController extends Controller
{
    public function __construct(protected NotificationPreferenceService $preferenceService) {}

    public function edit(Request $request, TenantContext $tenant): View
    {
        abort_unless($request->user()?->hasPermission('projects.notifications.manage'), 403);

        $preference = $this->preferenceService->getOrCreate($request->user(), $tenant->id());

        $this->authorize('view', $preference);

        return view('projects.notification-preferences.edit', [
            'preference' => $preference,
        ]);
    }

    public function update(
        UpdateNotificationPreferenceRequest $request,
        TenantContext $tenant,
    ): RedirectResponse {
        $preference = $this->preferenceService->update(
            $request->user(),
            $request->validated(),
            $tenant->id(),
            $request->user(),
        );

        $this->authorize('update', $preference);

        return redirect()
            ->route('notification-preferences.edit')
            ->with('status', 'notification-preferences-updated');
    }
}
