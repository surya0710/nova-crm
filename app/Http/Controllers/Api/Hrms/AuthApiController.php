<?php

namespace App\Http\Controllers\Api\Hrms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Hrms\Mobile\ChangePasswordRequest;
use App\Http\Requests\Hrms\Mobile\ForgotPasswordRequest;
use App\Http\Requests\Hrms\Mobile\RefreshTokenRequest;
use App\Http\Requests\Hrms\Mobile\ResetPasswordRequest;
use App\Http\Resources\Hrms\EmployeeResource;
use App\Http\Resources\Hrms\UserDeviceResource;
use App\Services\Hrms\MobileAuthService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthApiController extends Controller
{
    public function __construct(protected MobileAuthService $auth) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $devicePayload = $request->validate([
            'device_uuid' => ['nullable', 'string', 'max:191'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'push_token' => ['nullable', 'string', 'max:512'],
        ]);

        $result = $this->auth->login($request, array_filter($devicePayload));

        return ApiResponse::success([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'access_expires_at' => $result['access_expires_at'],
            'refresh_expires_at' => $result['refresh_expires_at'],
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
            ],
            'organizations' => $result['organizations'],
            'employee' => $result['employee']
                ? (new EmployeeResource($result['employee']))->resolve()
                : null,
            'device' => $result['device']
                ? (new UserDeviceResource($result['device']))->resolve()
                : null,
        ], __('Logged in successfully.'));
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $tokens = $this->auth->refresh($request->validated('refresh_token'));

        return ApiResponse::success($tokens, __('Token refreshed.'));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user(), $request);

        return ApiResponse::success(null, __('Logged out successfully.'));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->auth->changePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password'),
        );

        return ApiResponse::success(null, __('Password changed successfully.'));
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->auth->sendForgotPasswordLink($request->validated('email'));

        return ApiResponse::success(null, __('Password reset link sent.'));
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->auth->resetPassword($request->validated());

        return ApiResponse::success(null, __('Password has been reset.'));
    }
}
