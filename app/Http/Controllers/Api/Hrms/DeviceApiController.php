<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hrms\Mobile\RegisterDeviceRequest;
use App\Http\Resources\Hrms\UserDeviceResource;
use App\Models\UserDevice;
use App\Services\Hrms\MobileAuthService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceApiController extends Controller
{
    public function __construct(protected MobileAuthService $auth) {}

    public function store(RegisterDeviceRequest $request): JsonResponse
    {
        $device = $this->auth->registerDevice($request->user(), $request->validated(), $request);

        return ApiResponse::success(
            new UserDeviceResource($device),
            __('Device registered.'),
            status: 201,
        );
    }

    public function destroy(Request $request, UserDevice $device): JsonResponse
    {
        $this->auth->deactivateDevice($request->user(), $device);

        return ApiResponse::success(null, __('Device deactivated.'));
    }
}
