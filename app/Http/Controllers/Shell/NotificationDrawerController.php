<?php

namespace App\Http\Controllers\Shell;

use App\Http\Controllers\Controller;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationDrawerController extends Controller
{
    public function index(Request $request, TenantContext $tenant): JsonResponse
    {
        $organization = $tenant->get();
        abort_unless($organization, 404);

        $notifications = $request->user()->notifications()
            ->where('data->organization_id', $organization->id)
            ->latest()
            ->limit(30)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];

                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? $data['message'] ?? __('Notification'),
                    'body' => $data['body'] ?? $data['message'] ?? null,
                    'url' => $data['action_url'] ?? $data['url'] ?? route('notifications.index'),
                    'category' => $data['category'] ?? 'general',
                    'priority' => $data['priority'] ?? 'normal',
                    'workspace' => $data['workspace'] ?? null,
                    'read' => $notification->read_at !== null,
                    'created_at' => optional($notification->created_at)?->diffForHumans(),
                ];
            });

        $unread = $request->user()->unreadNotifications()
            ->where('data->organization_id', $organization->id)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread' => $unread,
        ]);
    }
}
