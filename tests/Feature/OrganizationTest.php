<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_organization_is_redirected_to_setup(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('organization.setup'));
    }

    public function test_user_can_create_organization_on_setup(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/organization/setup', [
            'name' => 'Nova Inc',
            'email' => 'contact@nova.test',
            'phone' => '+1 555 0100',
            'timezone' => 'UTC',
            'currency' => 'USD',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Nova Inc',
            'slug' => 'nova-inc',
        ]);

        $organization = Organization::where('slug', 'nova-inc')->first();

        $this->assertTrue($user->fresh()->belongsToOrganization($organization));
        $this->assertTrue($user->fresh()->isOwnerOf($organization));
        $this->assertTrue($user->fresh()->hasPermission('settings.manage', $organization));
    }

    public function test_authenticated_user_with_organization_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Acme Corp']);
        $organization->addMember($user, 'organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Acme Corp');
    }

    public function test_organization_owner_can_update_settings(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Old Name']);
        $organization->addMember($user, 'organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch('/organization/settings', [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'timezone' => 'Asia/Kolkata',
                'currency' => 'INR',
            ]);

        $response->assertRedirect(route('organization.edit'));

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'New Name',
            'currency' => 'INR',
        ]);
    }

    public function test_non_owner_cannot_update_organization_settings(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Old Name']);
        $organization->addMember($user, 'employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch('/organization/settings', [
                'name' => 'Hacked Name',
                'timezone' => 'UTC',
                'currency' => 'USD',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Old Name',
        ]);
    }

    public function test_user_can_switch_between_organizations(): void
    {
        $user = User::factory()->create();
        $orgA = Organization::factory()->create(['name' => 'Org A']);
        $orgB = Organization::factory()->create(['name' => 'Org B']);

        $orgA->addMember($user, 'organization-owner');
        $orgB->addMember($user, 'employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $orgA->id])
            ->post(route('organization.switch', $orgB));

        $response->assertRedirect();
        $response->assertSessionHas('current_organization_id', $orgB->id);
    }

    public function test_user_cannot_switch_to_organization_they_do_not_belong_to(): void
    {
        $user = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $orgA->addMember($user, 'organization-owner');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $orgA->id])
            ->post(route('organization.switch', $orgB));

        $response->assertForbidden();
    }

    public function test_tenant_context_tracks_current_organization(): void
    {
        $organization = Organization::factory()->create();
        $tenant = app(TenantContext::class);

        $this->assertNull($tenant->id());

        $tenant->set($organization);

        $this->assertEquals($organization->id, $tenant->id());
        $this->assertTrue($tenant->check());

        $tenant->clear();

        $this->assertNull($tenant->id());
    }

    public function test_organization_owner_can_upload_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $organization = Organization::factory()->create(['name' => 'Logo Corp']);
        $organization->addMember($user, 'organization-owner');

        $file = UploadedFile::fake()->create('logo.png', 100, 'image/png');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch('/organization/settings', [
                'name' => 'Logo Corp',
                'timezone' => 'UTC',
                'currency' => 'USD',
                'logo' => $file,
            ]);

        $response->assertRedirect(route('organization.edit'));

        $organization->refresh();

        $this->assertNotNull($organization->logo);
        Storage::disk('public')->assertExists($organization->logo);
    }

    public function test_registration_redirects_to_organization_setup(): void
    {
        $response = $this->post('/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('organization.setup'));
    }
}
