<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class InvoiceTest extends TestCase
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
                'description' => 'Monthly service',
                'quantity' => 1,
                'unit_price' => 500,
                'tax_rate' => 10,
                'discount_percent' => 0,
            ],
        ];
    }

    public function test_user_with_invoices_view_can_access_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('invoices.index'));

        $response->assertOk();
    }

    public function test_hr_user_cannot_access_invoices(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('invoices.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_create_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.store'), [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'currency' => 'USD',
                'items' => $this->sampleItems(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 550.00,
        ]);
    }

    public function test_manager_can_send_invoice_by_email(): void
    {
        Mail::fake();

        [$user, $organization] = $this->setupUserWithOrg('manager');
        $this->configureOrganizationMail($organization);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total' => 550,
            'subtotal' => 500,
            'tax_total' => 50,
            'created_by' => $user->id,
        ]);

        $invoice->items()->create([
            'description' => 'Monthly service',
            'quantity' => 1,
            'unit_price' => 500,
            'tax_rate' => 10,
            'discount_percent' => 0,
            'line_total' => 550,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.send', $invoice), ['email' => 'client@example.com']);

        $response->assertRedirect();
        Mail::assertSent(InvoiceMail::class);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'issued']);
    }

    public function test_can_create_invoice_from_quotation(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

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
            ->get(route('invoices.create', ['quotation' => $quotation->id]));

        $response->assertOk();
        $response->assertSee($quotation->number);
    }
}
