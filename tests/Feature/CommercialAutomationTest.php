<?php

namespace Tests\Feature;

use App\Models\CommercialReminderDispatch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_reminder_dispatches_once_per_day(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create(['is_active' => true]);
        $organization->addMember($user, 'manager');
        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'created_by' => $user->id,
            'email' => 'billing@example.test',
        ]);

        Invoice::factory()->create([
            'organization_id' => $organization->id,
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'status' => 'issued',
            'total' => 400,
            'amount_paid' => 0,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan('commercial:dispatch-reminders')->assertSuccessful();
        $this->assertSame(1, CommercialReminderDispatch::query()->where('reminder_type', 'due_soon')->count());
        $this->assertDatabaseHas('audit_logs', ['event' => 'reminder_sent']);

        $this->artisan('commercial:dispatch-reminders')->assertSuccessful();
        $this->assertSame(1, CommercialReminderDispatch::query()->where('reminder_type', 'due_soon')->count());
    }

    public function test_workflow_config_includes_commercial_triggers(): void
    {
        $triggers = config('workflows.triggers');

        foreach ([
            'invoice.issued',
            'invoice.due_soon',
            'invoice.overdue',
            'payment.confirmed',
            'quotation.created',
            'quotation.expiring',
            'sales_order.status_changed',
            'adjustment_note.created',
            'adjustment_note.applied',
        ] as $key) {
            $this->assertArrayHasKey($key, $triggers);
        }
    }

    public function test_owner_can_open_commercial_automation_settings(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->addMember($user, 'organization-owner');

        $this->actingAs($user)
            ->withSession(['current_organization_id' => $organization->id])
            ->get(route('organization.settings.commercial-automation.edit'))
            ->assertOk();
    }
}
