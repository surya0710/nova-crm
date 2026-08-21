<?php

namespace Tests\Feature;

use App\Mail\SalesOrderMail;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_manager_can_view_sales_orders_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('sales-orders.index'))
            ->assertOk();
    }

    public function test_hr_cannot_access_sales_orders(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('sales-orders.index'))
            ->assertForbidden();
    }

    public function test_manager_can_create_and_confirm_sales_order(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('sales-orders.store'), [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'order_date' => now()->toDateString(),
                'expected_delivery_date' => now()->addDays(10)->toDateString(),
                'currency' => 'USD',
                'terms' => 'FOB destination',
                'items' => [[
                    'description' => 'Implementation',
                    'sku' => 'IMP-1',
                    'hsn_sac' => '9983',
                    'unit' => 'each',
                    'quantity' => 2,
                    'unit_price' => 500,
                    'tax_rate' => 10,
                    'discount_percent' => 0,
                ]],
            ])
            ->assertRedirect();

        $salesOrder = SalesOrder::query()->first();
        $this->assertSame('draft', $salesOrder->status);
        $this->assertSame(1100.0, (float) $salesOrder->total);
        $this->assertSame('FOB destination', $salesOrder->terms);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('sales-orders.status.update', $salesOrder), ['status' => 'confirmed'])
            ->assertRedirect();

        $this->assertSame('confirmed', $salesOrder->fresh()->status);
    }

    public function test_sales_order_pdf_downloads(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);
        $salesOrder = SalesOrder::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);
        $salesOrder->items()->create([
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('sales-orders.pdf', $salesOrder));

        $response->assertOk();
        $content = $response->getContent();
        if ($content === '' || $content === false) {
            $content = $response->streamedContent();
        }
        $this->assertStringStartsWith('%PDF', (string) $content);
    }

    public function test_sales_order_email_ccs_sender(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'sender@acme.test']);
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);
        $salesOrder = SalesOrder::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);
        $salesOrder->items()->create([
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('sales-orders.send', $salesOrder), [
                'email' => 'client@example.com',
                'cc' => 'accounts@example.com',
                'message' => 'Please confirm.',
            ])
            ->assertRedirect();

        Mail::assertSent(SalesOrderMail::class, function (SalesOrderMail $mail) {
            return $mail->hasTo('client@example.com')
                && $mail->hasCc('sender@acme.test')
                && $mail->hasCc('accounts@example.com');
        });
    }
}
