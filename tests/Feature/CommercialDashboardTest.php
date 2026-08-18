<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Dashboard\DashboardWidgetService;
use App\Services\Dashboard\QuickActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommercialDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(DashboardWidgetService::class)->seedSystemWidgets();
        app(QuickActionService::class)->seedSystemActions();
    }

    public function test_commercial_widgets_are_registered(): void
    {
        $this->assertDatabaseHas('dashboard_widgets', ['widget_key' => 'commercial_quotations']);
        $this->assertDatabaseHas('dashboard_widgets', ['widget_key' => 'commercial_invoices']);
        $this->assertDatabaseHas('dashboard_widgets', ['widget_key' => 'commercial_revenue']);
    }

    public function test_quotation_widget_returns_counts_and_conversion_rate(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $organization->addMember($user, 'manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'accepted',
            'total' => 1000,
            'created_by' => $user->id,
        ]);

        Quotation::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'converted',
            'total' => 500,
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/dashboard/widgets/commercial_quotations/data', [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('count', 2);
        $response->assertJsonPath('accepted_count', 1);
        $response->assertJsonPath('converted_count', 1);
        $response->assertJsonPath('conversion_rate', 50);
    }

    public function test_invoice_widget_tracks_outstanding_and_overdue(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['plan' => 'enterprise']);
        $organization->addMember($user, 'manager');

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'issued',
            'total' => 400,
            'amount_paid' => 100,
            'due_date' => now()->subDays(5)->toDateString(),
            'created_by' => $user->id,
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total' => 200,
            'amount_paid' => 200,
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/dashboard/widgets/commercial_invoices/data', [
            'X-Organization-Id' => (string) $organization->id,
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('count', 2);
        $response->assertJsonPath('paid_count', 1);
        $response->assertJsonPath('outstanding_count', 1);
        $response->assertJsonPath('overdue_count', 1);
        $response->assertJsonPath('outstanding_value', 300);
    }
}
