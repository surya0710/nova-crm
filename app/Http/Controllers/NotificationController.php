<?php

namespace App\Http\Controllers;

use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        $organization = $tenant->get();

        $notifications = $request->user()
            ->notifications()
            ->when($organization, fn ($q) => $q->where('data->organization_id', $organization->id))
            ->latest()
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            'organization' => $organization,
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->where('id', $notification)->firstOrFail();
        $item->markAsRead();

        $url = $item->data['action_url'] ?? route('notifications.index');

        return redirect($url);
    }

    public function markAllRead(Request $request, TenantContext $tenant): RedirectResponse
    {
        $organization = $tenant->get();

        $request->user()
            ->unreadNotifications()
            ->when($organization, fn ($q) => $q->where('data->organization_id', $organization->id))
            ->update(['read_at' => now()]);

        return back()->with('status', 'notifications-read');
    }
}
