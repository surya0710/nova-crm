<?php

namespace Tests\Feature\Hrms\MobileApi;

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HrmsMobileAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_access_and_refresh_tokens_and_registers_device(): void
    {
        [$organization, $user, $employee] = $this->essUser();

        $response = $this->postJson('/api/v1/hrms/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_uuid' => 'device-abc-1',
            'device_name' => 'Pixel 8',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'push_token' => 'fcm-token-xyz',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'refresh_token',
                    'access_expires_at',
                    'refresh_expires_at',
                    'user' => ['id', 'email'],
                    'organizations',
                    'employee',
                    'device',
                ],
            ]);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_uuid' => 'device-abc-1',
            'platform' => 'android',
            'is_active' => true,
        ]);

        $this->assertNotNull($response->json('data.employee.id'));
        $this->assertSame($employee->id, $response->json('data.employee.id'));
        $this->assertSame($organization->id, $response->json('data.organizations.0.id'));
    }

    public function test_login_rejects_invalid_credentials_with_standard_error_envelope(): void
    {
        [, $user] = $this->essUser();

        $this->postJson('/api/v1/hrms/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);
    }

    public function test_refresh_rotates_tokens(): void
    {
        [, $user] = $this->essUser();

        $login = $this->postJson('/api/v1/hrms/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_uuid' => 'refresh-device',
        ])->assertOk();

        $refresh = $login->json('data.refresh_token');

        $this->postJson('/api/v1/hrms/auth/refresh', [
            'refresh_token' => $refresh,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
    }

    public function test_logout_revokes_current_device_tokens(): void
    {
        [$organization, $user] = $this->essUser();

        $login = $this->postJson('/api/v1/hrms/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_uuid' => 'logout-device',
        ])->assertOk();

        $token = $login->json('data.access_token');

        $this->withToken($token)
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson('/api/v1/hrms/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'device_uuid' => 'logout-device',
            'is_active' => false,
        ]);
    }

    public function test_device_registration_and_deletion(): void
    {
        [$organization, $user, $employee] = $this->essUser();
        Sanctum::actingAs($user, [config('hrms.mobile.access_token_ability')]);

        $this->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson('/api/v1/hrms/devices', [
                'device_uuid' => 'reg-device-1',
                'device_name' => 'iPhone',
                'platform' => 'ios',
                'push_token' => 'apns-1',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $device = UserDevice::query()->where('device_uuid', 'reg-device-1')->firstOrFail();
        $this->assertSame($employee->id, $device->employee_id);

        $this->withHeaders(['X-Organization-Id' => $organization->id])
            ->deleteJson('/api/v1/hrms/devices/'.$device->id)
            ->assertOk();

        $this->assertFalse($device->fresh()->is_active);
    }

    public function test_change_password_requires_current_password(): void
    {
        [$organization, $user] = $this->essUser();
        Sanctum::actingAs($user, [config('hrms.mobile.access_token_ability')]);

        $this->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson('/api/v1/hrms/auth/change-password', [
                'current_password' => 'wrong',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * @return array{0: Organization, 1: User, 2: Employee}
     */
    protected function essUser(string $role = 'employee'): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $organization->addMember($user, $role);

        $employee = Employee::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        return [$organization, $user, $employee];
    }
}
