<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_timeline_includes_quotation_and_invoice_events(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $quotation = Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'subtotal' => 100,
            'tax_total' => 0,
            'total' => 100,
            'created_by' => $user->id,
        ]);

        $quotation->items()->create([
            'description' => 'Consulting',
            'quantity' => 1,
            'unit_price' => 100,
            'tax_rate' => 0,
            'discount_percent' => 0,
            'line_total' => 100,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation), ['status' => 'sent'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->patch(route('quotations.status.update', $quotation->fresh()), ['status' => 'accepted'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
            'total' => 100,
            'created_by' => $user->id,
        ]);

        $invoice->items()->create([
            'description' => 'Line',
            'quantity' => 1,
            'unit_price' => 100,
            'tax_rate' => 0,
            'discount_percent' => 0,
            'line_total' => 100,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('invoices.issue', $invoice))
            ->assertRedirect();

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('Quotation created');
        $response->assertSee('Quotation sent');
        $response->assertSee('Quotation accepted');
        $response->assertSee('Invoice created');
        $response->assertSee('Invoice issued');
        $response->assertSee($quotation->number);
        $response->assertSee($invoice->number);
    }
}
