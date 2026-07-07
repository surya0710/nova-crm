<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setupUserWithOrg(string $role = 'manager'): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, $role);

        return [$user, $organization];
    }

    public function test_manager_can_view_reports(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('manager');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee(__('Reports & Analytics'));
    }

    public function test_hr_user_can_view_reports(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('hr');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.index'));

        $response->assertOk();
    }

    public function test_employee_cannot_view_reports(): void
    {
        [$user, $organization] = $this->setupUserWithOrg('employee');

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.index'));

        $response->assertForbidden();
    }

    public function test_reports_show_revenue_and_conversion_metrics(): void
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

        Payment::factory()->create([
            'organization_id' => $organization->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 500,
            'payment_date' => now(),
            'recorded_by' => $user->id,
        ]);

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'won',
            'assigned_to' => $user->id,
            'created_by' => $user->id,
        ]);

        Lead::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'lost',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee(__('Revenue collected'));
        $response->assertSee('500.00');
        $response->assertSee('50%');
        $response->assertSee($user->name);
    }
}
