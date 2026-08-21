<?php

namespace Tests\Feature;

use App\Mail\TestOrganizationMail;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class OrganizationMailTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    protected function setupOwner(): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        return [$user, $organization];
    }

    public function test_owner_can_save_organization_mail_settings(): void
    {
        [$user, $organization] = $this->setupOwner();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('organization.update'), [
                'name' => $organization->name,
                'timezone' => $organization->timezone,
                'currency' => $organization->currency,
                'mail_enabled' => '1',
                'mail_driver' => 'log',
                'mail_from_address' => 'noreply@acme.test',
                'mail_from_name' => 'Acme Billing',
            ]);

        $response->assertRedirect(route('organization.edit'));

        $organization->refresh();
        $this->assertTrue($organization->settings['mail']['enabled']);
        $this->assertSame('log', $organization->settings['mail']['provider']);
        $this->assertSame('log', $organization->settings['mail']['driver']);
        $this->assertSame('noreply@acme.test', $organization->settings['mail']['from_address']);
    }

    public function test_legacy_log_driver_without_provider_is_still_configured(): void
    {
        $organization = Organization::factory()->create();
        $settings = $organization->settings ?? [];
        $settings['mail'] = [
            'enabled' => true,
            'driver' => 'log',
            'from_address' => 'billing@example.test',
            'from_name' => 'Acme',
        ];
        $organization->update(['settings' => $settings]);

        $config = app(\App\Services\OrganizationMailConfig::class)->for($organization->fresh());

        $this->assertTrue($config->isConfigured());
        $this->assertSame('log', $config->driver());
    }

    public function test_owner_can_send_test_email_using_organization_mailer(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupOwner();
        $this->configureOrganizationMail($organization);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('organization.test-mail'), [
                'test_email' => 'owner@example.com',
            ]);

        $response->assertRedirect(route('organization.edit'));
        $response->assertSessionHas('status', 'organization-mail-test-sent');

        Mail::assertSent(TestOrganizationMail::class);
    }

    public function test_client_email_blocked_when_organization_mail_not_configured(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupOwner();

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('customers.send', $customer), [
                'email' => 'client@example.com',
                'subject' => 'Hello',
                'message' => 'Test',
            ]);

        $response->assertRedirect(route('customers.show', $customer));
        $response->assertSessionHas('error');

        Mail::assertNothingSent();
    }
}
