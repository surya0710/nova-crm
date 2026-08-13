<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_wfh_assignments')) {
            Schema::create('employee_wfh_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id');
                $table->string('policy_type', 40);
                $table->json('weekdays')->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('reason', 500)->nullable();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'employee_wfh_assignments_org_id_unique');
                $table->foreign('employee_id', 'employee_wfh_assignments_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->index(
                    ['organization_id', 'employee_id', 'is_active'],
                    'employee_wfh_assignments_org_employee_active_idx'
                );
                $table->index(
                    ['organization_id', 'policy_type', 'is_active'],
                    'employee_wfh_assignments_org_type_active_idx'
                );
                $table->index(
                    ['organization_id', 'effective_from', 'effective_to'],
                    'employee_wfh_assignments_org_effective_idx'
                );
            });
        }

        if (! Schema::hasTable('wfh_requests')) {
            Schema::create('wfh_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id');
                $table->date('work_date');
                $table->string('reason', 1000)->nullable();
                $table->string('status', 40)->default('draft');
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'id'], 'wfh_requests_org_id_unique');
                $table->foreign('employee_id', 'wfh_requests_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->index(['organization_id', 'employee_id', 'work_date'], 'wfh_requests_org_employee_date_idx');
                $table->index(['organization_id', 'status'], 'wfh_requests_org_status_idx');
                $table->index(['organization_id', 'work_date'], 'wfh_requests_org_work_date_idx');
            });
        }

        if (! Schema::hasTable('wfh_approval_steps')) {
            Schema::create('wfh_approval_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('wfh_request_id');
                $table->unsignedInteger('step_order');
                $table->unsignedBigInteger('approver_employee_id')->nullable();
                $table->unsignedBigInteger('approver_user_id')->nullable();
                $table->string('status', 40)->default('pending');
                $table->timestamp('acted_at')->nullable();
                $table->string('comments', 1000)->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'id'], 'wfh_approval_steps_org_id_unique');
                $table->unique(
                    ['organization_id', 'wfh_request_id', 'step_order'],
                    'wfh_approval_steps_org_request_order_unique'
                );
                $table->foreign('wfh_request_id', 'wfh_approval_steps_request_fk')
                    ->references('id')->on('wfh_requests')->cascadeOnDelete();
                $table->foreign('approver_employee_id', 'wfh_approval_steps_approver_employee_fk')
                    ->references('id')->on('employees')->nullOnDelete();
                $table->foreign('approver_user_id', 'wfh_approval_steps_approver_user_fk')
                    ->references('id')->on('users')->nullOnDelete();
                $table->index(['organization_id', 'status'], 'wfh_approval_steps_org_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wfh_approval_steps');
        Schema::dropIfExists('wfh_requests');
        Schema::dropIfExists('employee_wfh_assignments');
    }
};
