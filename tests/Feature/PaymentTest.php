<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_manager_can_view_payments_index(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('payments.index'));

        $response->assertOk();
    }

    public function test_hr_user_cannot_access_payments(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('payments.index'));

        $response->assertForbidden();
    }

    public function test_manager_can_record_payment(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 1000,
            'amount_paid' => 0,
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('payments.store'), [
                'invoice_id' => $invoice->id,
                'amount' => 400,
                'payment_date' => now()->toDateString(),
                'method' => 'bank_transfer',
                'reference' => 'TXN-123',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 400,
            'reference' => 'TXN-123',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'amount_paid' => 400,
            'status' => 'partial',
        ]);
    }

    public function test_payment_cannot_exceed_invoice_balance(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 500,
            'amount_paid' => 0,
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->post(route('payments.store'), [
                'invoice_id' => $invoice->id,
                'amount' => 600,
                'payment_date' => now()->toDateString(),
                'method' => 'cash',
            ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_deleting_payment_recalculates_invoice(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'total' => 1000,
            'amount_paid' => 500,
            'status' => 'partial',
            'created_by' => $user->id,
        ]);

        $payment = Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 500,
            'recorded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->delete(route('payments.destroy', $payment));

        $response->assertRedirect(route('payments.index'));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'amount_paid' => 0,
            'status' => 'sent',
        ]);
    }
}
