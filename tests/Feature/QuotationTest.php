<?php

namespace Tests\Feature;

use App\Mail\QuotationMail;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class QuotationTest extends TestCase
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

    protected function sampleItems(): array
    {
        return [
            [
                'description' => 'Consulting hours',
                'quantity' => 10,
                'unit_price' => 100,
                'tax_rate' => 10,
                'discount_percent' => 0,
            ],
        ];
    }

    public function test_user_with_quotations_view_can_access_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('quotations.index'));

        $response->assertOk();
        $response->assertSee('Quotations');
    }

    public function test_hr_user_cannot_access_quotations(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('quotations.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_create_quotation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.store'), [
                'customer_id' => $customer->id,
                'title' => 'Website Project',
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays(14)->toDateString(),
                'currency' => 'USD',
                'notes' => 'Net 30 payment terms.',
                'items' => $this->sampleItems(),
            ]);

        $response->assertRedirect();

        $quotation = Quotation::query()->first();
        $this->assertNotNull($quotation);
        $this->assertDatabaseHas('quotations', [
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'title' => 'Website Project',
            'created_by' => $user->id,
        ]);
        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'description' => 'Consulting hours',
        ]);
        $this->assertSame(1100.0, (float) $quotation->total);
    }

    public function test_manager_can_create_quotation_with_product_line(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $product = Product::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Support Plan',
            'unit_price' => 200,
            'tax_rate' => 5,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.store'), [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'description' => 'Support Plan',
                        'quantity' => 2,
                        'unit_price' => 200,
                        'tax_rate' => 5,
                        'discount_percent' => 0,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('quotation_items', [
            'product_id' => $product->id,
            'line_total' => 420.00,
        ]);
    }

    public function test_user_can_update_quotation_status_from_show_page(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), [
                'status' => 'sent',
            ]);

        $response->assertRedirect(route('quotations.show', $quotation));
        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'sent',
        ]);
    }

    public function test_sales_executive_cannot_delete_quotation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('sales-executive');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('quotations.destroy', $quotation));

        $response->assertForbidden();
    }

    public function test_manager_can_send_quotation_by_email(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.send', $quotation), [
                'email' => 'customer@example.com',
                'message' => 'Please review at your earliest convenience.',
            ]);

        $response->assertRedirect(route('quotations.show', $quotation));
        $response->assertSessionHas('status', 'quotation-email-sent');

        Mail::assertSent(QuotationMail::class, function (QuotationMail $mail) use ($quotation) {
            return $mail->quotation->is($quotation)
                && $mail->personalMessage === 'Please review at your earliest convenience.';
        });

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'sent',
        ]);
    }

    public function test_hr_user_cannot_send_quotation_email(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg('hr');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'customer@example.com',
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('quotations.send', $quotation), [
                'email' => 'customer@example.com',
            ]);

        $response->assertForbidden();
        Mail::assertNothingSent();
    }
}
