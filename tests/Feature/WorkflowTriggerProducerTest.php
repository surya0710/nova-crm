<?php

namespace Tests\Feature;

use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Events\InvoiceCreated;
use App\Events\LeadAssigned;
use App\Events\LeadConverted;
use App\Events\LeadCreated;
use App\Events\LeadUpdated;
use App\Events\OpportunityCreated;
use App\Events\OpportunityStageChanged;
use App\Events\PaymentReceived;
use App\Events\WorkflowDomainEvent;
use App\Models\Lead;
use App\Models\MetadataFieldDefinition;
use App\Models\Organization;
use App\Models\User;
use App\Services\Assignment\AssignmentService;
use App\Services\CustomerService;
use App\Services\InvoiceService;
use App\Services\LeadConversionService;
use App\Services\LeadService;
use App\Services\OpportunityService;
use App\Services\PaymentService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class WorkflowTriggerProducerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owning_services_emit_tenant_subject_and_actor_payloads(): void
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create();
        $assignee = User::factory()->create();
        $organization->addMember($actor, 'organization-owner');
        $organization->addMember($assignee, 'sales-executive');
        app(TenantContext::class)->set($organization);
        foreach ([
            ['entity_type' => 'lead', 'key' => 'workflow_score'],
            ['entity_type' => 'customer', 'key' => 'workflow_segment'],
            ['entity_type' => 'opportunity', 'key' => 'workflow_band'],
        ] as $field) {
            MetadataFieldDefinition::query()->create([
                'organization_id' => $organization->id,
                ...$field,
                'label' => str($field['key'])->headline()->toString(),
                'type' => 'text',
                'status' => 'active',
                'published_at' => now(),
                'activated_at' => now(),
            ]);
        }

        $events = [
            LeadCreated::class,
            LeadUpdated::class,
            LeadAssigned::class,
            LeadConverted::class,
            CustomerCreated::class,
            CustomerUpdated::class,
            OpportunityCreated::class,
            OpportunityStageChanged::class,
            InvoiceCreated::class,
            PaymentReceived::class,
        ];
        Event::fake($events);

        $transactionManager = app('db.transactions');
        app()->offsetUnset('db.transactions');

        try {
            $lead = app(LeadService::class)->create([
                'organization_id' => $organization->id,
                'name' => 'Workflow producer lead',
                'email' => 'workflow-producer@example.test',
                'source' => 'manual_entry',
                'status' => 'new',
                'priority' => 'medium',
            ], $actor, metadataValues: ['workflow_score' => 'created']);
            app(LeadService::class)->update($lead, [], $actor, ['workflow_score' => 'updated']);
            app(AssignmentService::class)->assignOwner($lead, $assignee->id, $actor);

            $customer = app(CustomerService::class)->create([
                'organization_id' => $organization->id,
                'name' => 'Workflow Customer',
                'email' => 'workflow-customer@example.test',
                'status' => 'active',
            ], $actor, ['workflow_segment' => 'created']);
            app(CustomerService::class)->update($customer, [], $actor, ['workflow_segment' => 'updated']);

            $opportunity = app(OpportunityService::class)->create([
                'organization_id' => $organization->id,
                'customer_id' => $customer->id,
                'title' => 'Workflow Opportunity',
                'stage' => 'qualification',
                'amount' => 1000,
                'currency' => 'USD',
            ], $actor, ['workflow_band' => 'created']);
            app(OpportunityService::class)->update(
                $opportunity,
                ['stage' => 'proposal'],
                $actor,
                ['workflow_band' => 'updated'],
            );

            $invoice = app(InvoiceService::class)->create($organization, [
                'customer_id' => $customer->id,
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'currency' => 'USD',
                'items' => [[
                    'product_id' => null,
                    'description' => 'Workflow item',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'tax_rate' => 0,
                    'discount_percent' => 0,
                ]],
            ], $actor);
            $invoice->updateQuietly(['status' => 'issued']);
            app(PaymentService::class)->record($organization, $invoice->fresh(), [
                'amount' => 100,
                'payment_date' => now()->toDateString(),
                'method' => 'bank_transfer',
            ], $actor);

            $convertible = Lead::factory()->create([
                'organization_id' => $organization->id,
                'created_by' => $actor->id,
                'status' => 'qualified',
                'email' => 'workflow-convert@example.test',
            ]);
            app(LeadConversionService::class)->convert($convertible, [
                'name' => 'Converted Workflow Lead',
                'email' => 'workflow-convert@example.test',
                'create_opportunity' => true,
            ], $actor);
        } finally {
            app()->instance('db.transactions', $transactionManager);
        }

        foreach ($events as $eventClass) {
            Event::assertDispatched($eventClass, function (WorkflowDomainEvent $event) use ($organization, $actor): bool {
                return $event->organizationId === $organization->id
                    && $event->subjectId !== null
                    && $event->subjectSnapshot !== []
                    && (int) ($event->payload['actor_id'] ?? 0) === $actor->id;
            });
        }
        Event::assertDispatched(LeadCreated::class, fn (LeadCreated $event): bool => data_get($event->subjectSnapshot, 'custom_fields.workflow_score') === 'created');
        Event::assertDispatched(LeadUpdated::class, fn (LeadUpdated $event): bool => data_get($event->subjectSnapshot, 'custom_fields.workflow_score') === 'updated'
            && in_array('custom_fields', $event->payload['changes'], true));
        Event::assertDispatched(CustomerCreated::class, fn (CustomerCreated $event): bool => data_get($event->subjectSnapshot, 'custom_fields.workflow_segment') === 'created');
        Event::assertDispatched(CustomerUpdated::class, fn (CustomerUpdated $event): bool => data_get($event->subjectSnapshot, 'custom_fields.workflow_segment') === 'updated'
            && in_array('custom_fields', $event->payload['changes'], true));
        Event::assertDispatched(OpportunityCreated::class, fn (OpportunityCreated $event): bool => data_get($event->subjectSnapshot, 'custom_fields.workflow_band') === 'created');
        Event::assertDispatched(OpportunityStageChanged::class, fn (OpportunityStageChanged $event): bool => data_get($event->subjectSnapshot, 'custom_fields.workflow_band') === 'updated');
    }

    public function test_after_commit_domain_event_is_suppressed_when_transaction_rolls_back(): void
    {
        Event::fake([LeadCreated::class]);
        $originalConnection = (string) config('database.default');
        $isolatedConnection = 'workflow_after_commit_test';
        config([
            "database.connections.{$isolatedConnection}" => config("database.connections.{$originalConnection}"),
            'database.default' => $isolatedConnection,
        ]);
        DB::purge($isolatedConnection);

        try {
            DB::connection()->transaction(function (): void {
                $organization = Organization::factory()->create();
                $actor = User::factory()->create();
                $organization->addMember($actor, 'organization-owner');
                app(TenantContext::class)->set($organization);

                app(LeadService::class)->create([
                    'organization_id' => $organization->id,
                    'name' => 'Rolled back workflow lead',
                    'source' => 'manual_entry',
                    'status' => 'new',
                    'priority' => 'medium',
                ], $actor);

                throw new RuntimeException('Rollback producer transaction.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback producer transaction.', $exception->getMessage());
        } finally {
            config(['database.default' => $originalConnection]);
            DB::purge($isolatedConnection);
            app(TenantContext::class)->set(null);
        }

        Event::assertNotDispatched(LeadCreated::class);
    }
}
