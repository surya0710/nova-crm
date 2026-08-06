<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Resources\Hrms\NotificationResource;
use App\Services\Hrms\HRMSApiFacadeService;
use App\Support\Api\ApiQuery;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function __construct(protected HRMSApiFacadeService $facade) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->facade->notifications()->paginate(
            $request->user(),
            ApiQuery::perPage($request),
        );

        return ApiResponse::paginated(
            $paginator,
            mapItem: fn ($item) => (new NotificationResource($item))->resolve(),
        );
    }

    public function count(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'unread' => $this->facade->notifications()->unreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $item = $this->facade->notifications()->markRead($request->user(), $notification);

        return ApiResponse::success(new NotificationResource($item), __('Notification marked as read.'));
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->facade->notifications()->markAllRead($request->user());

        return ApiResponse::success(['updated' => $count], __('All notifications marked as read.'));
    }
}
