<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type', 100);
            $table->json('trigger_config')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->unsignedSmallInteger('concurrency_limit')->default(1);
            $table->unsignedInteger('execution_timeout_seconds')->default(300);
            $table->timestamp('enabled_at')->nullable();
            $table->foreignId('enabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'workflows_org_id_unique');
            $table->index(['organization_id', 'status', 'trigger_type'], 'workflows_org_status_trigger_idx');
        });

        Schema::create('workflow_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedInteger('workflow_version')->default(1);
            $table->unsignedBigInteger('parent_condition_id')->nullable();
            $table->string('type', 20)->default('condition');
            $table->string('boolean_operator', 10)->nullable();
            $table->string('field')->nullable();
            $table->string('operator', 50)->nullable();
            $table->json('value')->nullable();
            $table->boolean('negated')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'workflow_conditions_org_id_unique');
            $table->foreign(['organization_id', 'workflow_id'], 'workflow_conditions_org_workflow_fk')
                ->references(['organization_id', 'id'])->on('workflows')->cascadeOnDelete();
            $table->foreign(['organization_id', 'parent_condition_id'], 'workflow_conditions_org_parent_fk')
                ->references(['organization_id', 'id'])->on('workflow_conditions')->cascadeOnDelete();
            $table->index(['organization_id', 'workflow_id', 'workflow_version', 'parent_condition_id', 'position'], 'workflow_conditions_tree_idx');
        });

        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedInteger('workflow_version')->default(1);
            $table->string('type', 100);
            $table->string('name')->nullable();
            $table->json('configuration');
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'workflow_actions_org_id_unique');
            $table->foreign(['organization_id', 'workflow_id'], 'workflow_actions_org_workflow_fk')
                ->references(['organization_id', 'id'])->on('workflows')->cascadeOnDelete();
            $table->index(['organization_id', 'workflow_id', 'workflow_version', 'status', 'position'], 'workflow_actions_dispatch_idx');
        });

        Schema::create('workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workflow_id');
            $table->unsignedInteger('workflow_version');
            $table->nullableMorphs('trigger_subject', 'workflow_executions_subject_idx');
            $table->json('trigger_subject_snapshot')->nullable();
            $table->json('trigger_payload')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('idempotency_key', 191)->nullable();
            $table->string('lock_owner', 191)->nullable();
            $table->timestamp('lock_acquired_at')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->unsignedInteger('current_action_position')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'workflow_executions_org_id_unique');
            $table->unique(['organization_id', 'workflow_id', 'idempotency_key'], 'workflow_executions_idempotency_unique');
            $table->foreign(['organization_id', 'workflow_id'], 'workflow_executions_org_workflow_fk')
                ->references(['organization_id', 'id'])->on('workflows')->cascadeOnDelete();
            $table->index(['organization_id', 'workflow_id', 'status'], 'workflow_executions_queue_idx');
            $table->index(['organization_id', 'status', 'heartbeat_at'], 'workflow_executions_lease_idx');
        });

        Schema::create('workflow_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workflow_execution_id');
            $table->unsignedBigInteger('workflow_action_id')->nullable();
            $table->unsignedBigInteger('workflow_condition_id')->nullable();
            $table->string('level', 20)->default('info');
            $table->string('event', 100);
            $table->string('status', 30)->nullable();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->foreign(['organization_id', 'workflow_execution_id'], 'workflow_logs_org_execution_fk')
                ->references(['organization_id', 'id'])->on('workflow_executions')->cascadeOnDelete();
            $table->foreign('workflow_action_id', 'workflow_logs_action_fk')
                ->references('id')->on('workflow_actions')->nullOnDelete();
            $table->foreign('workflow_condition_id', 'workflow_logs_condition_fk')
                ->references('id')->on('workflow_conditions')->nullOnDelete();
            $table->index(['organization_id', 'workflow_execution_id', 'occurred_at'], 'workflow_logs_execution_time_idx');
            $table->index(['organization_id', 'level', 'occurred_at'], 'workflow_logs_level_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_execution_logs');
        Schema::dropIfExists('workflow_executions');
        Schema::dropIfExists('workflow_actions');
        Schema::dropIfExists('workflow_conditions');
        Schema::dropIfExists('workflows');
    }
};
