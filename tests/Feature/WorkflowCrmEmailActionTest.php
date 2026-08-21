<?php

namespace Tests\Feature;

use App\Events\CustomerCreated;
use App\Events\CustomerLifecycleChanged;
use App\Events\InvoiceDueSoon;
use App\Events\InvoiceOverdue;
use App\Events\QuotationSent;
use App\Events\TicketEscalated;
use App\Listeners\RunTriggeredWorkflows;
use App\Models\CrmEmailMessage;
use App\Models\CrmEmailTemplate;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowExecution;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\ConfiguresOrganizationMail;
use Tests\TestCase;

class WorkflowCrmEmailActionTest extends TestCase
{
    use ConfiguresOrganizationMail;
    use RefreshDatabase;

    public function test_crm_email_actions_and_events_are_registered(): void
    {
        $this->assertSame(
            \App\Workflow\Actions\SendCrmEmailAction::class,
            config('workflows.actions.send_crm_email.handler')
        );
        $this->assertSame(
            \App\Workflow\Actions\SendCrmEmailAction::class,
            config('workflows.actions.send_crm_email_template.handler')
        );
        $this->assertSame('quotation', config('workflows.triggers')['quotation.sent']['entity'] ?? null);
        $this->assertSame('ticket', config('workflows.triggers')['ticket.escalated']['entity'] ?? null);
        $this->assertSame('customer', config('workflows.triggers')['customer.lifecycle_changed']['entity'] ?? null);

        foreach ([
            QuotationSent::class,
            TicketEscalated::class,
            CustomerLifecycleChanged::class,
            InvoiceDueSoon::class,
            InvoiceOverdue::class,
        ] as $event) {
            $this->assertTrue(Event::hasListeners($event), "Missing workflow listener for {$event}");
        }
    }

    public function test_customer_created_workflow_sends_email_once_and_stays_tenant_safe(): void
    {
        Mail::fake();

        [$organization, $actor] = $this->organizationWithOwner();
        [$other] = $this->organizationWithOwner();
        $this->configureOrganizationMail($organization);
        app(TenantContext::class)->set($organization);

        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'customer.created',
            'created_by' => $actor->id,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'send_crm_email',
            'configuration' => [
                'recipient' => 'customer',
                'subject' => 'Welcome',
                'message' => 'Hello {{customer.name}}',
                'cc' => 'archive@acme.test',
            ],
        ]);
        Workflow::factory()->active()->create([
            'organization_id' => $other->id,
            'trigger_type' => 'customer.created',
        ]);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'client@example.com',
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
        ]);

        $listener = app(RunTriggeredWorkflows::class);
        $event = CustomerCreated::forModel($customer, ['actor_id' => $actor->id], eventId: 'cust-email-1');
        $listener->handle($event);
        $listener->handle($event);

        $this->assertDatabaseCount('workflow_executions', 1);
        $this->assertSame(WorkflowExecution::STATUS_COMPLETED, WorkflowExecution::query()->firstOrFail()->status);
        $this->assertDatabaseCount('crm_email_messages', 1);

        $message = CrmEmailMessage::query()->firstOrFail();
        $this->assertSame($organization->id, $message->organization_id);
        $this->assertSame(['client@example.com'], $message->to);
        $this->assertContains('archive@acme.test', $message->cc ?? []);
        $this->assertContains($message->status, ['queued', 'sending', 'sent', 'delivered']);
    }

    public function test_template_action_resolves_variables_for_record_owner(): void
    {
        Mail::fake();

        [$organization, $actor] = $this->organizationWithOwner();
        $this->configureOrganizationMail($organization);
        app(TenantContext::class)->set($organization);

        $template = CrmEmailTemplate::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Owner ping',
            'subject' => 'Follow up {{customer.name}}',
            'body' => 'Owner copy for {{customer.email}}',
            'category' => 'general',
            'is_active' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $workflow = Workflow::factory()->active()->create([
            'organization_id' => $organization->id,
            'trigger_type' => 'customer.created',
            'created_by' => $actor->id,
        ]);
        WorkflowAction::factory()->create([
            'organization_id' => $organization->id,
            'workflow_id' => $workflow->id,
            'type' => 'send_crm_email_template',
            'configuration' => [
                'recipient' => 'record_owner',
                'template_id' => (string) $template->id,
            ],
        ]);

        $customer = Customer::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Acme',
            'email' => 'client@example.com',
            'created_by' => $actor->id,
            'assigned_to' => $actor->id,
        ]);

        app(RunTriggeredWorkflows::class)->handle(
            CustomerCreated::forModel($customer, ['actor_id' => $actor->id], eventId: 'cust-email-template-1')
        );

        $message = CrmEmailMessage::query()->firstOrFail();
        $this->assertSame([$actor->email], $message->to);
        $this->assertSame($template->id, $message->template_id);
        $this->assertStringContainsString('Acme', (string) $message->subject);
    }

    /** @return array{Organization, User} */
    protected function organizationWithOwner(): array
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create();
        $organization->addMember($actor, 'organization-owner');

        return [$organization, $actor];
    }
}
