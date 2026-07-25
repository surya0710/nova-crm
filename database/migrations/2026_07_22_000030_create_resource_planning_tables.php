<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('working_hours_per_day', 5, 2);
            $table->json('working_days');
            $table->string('timezone')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->unique(
                ['organization_id', 'employee_id', 'effective_from'],
                'resource_calendars_org_employee_from_unique'
            );
            $table->index(
                ['organization_id', 'employee_id', 'effective_from'],
                'resource_calendars_org_employee_from_idx'
            );
        });

        Schema::create('resource_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('allocation_type', 50);
            $table->unsignedTinyInteger('allocation_percentage');
            $table->decimal('planned_hours', 8, 2)->nullable();
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'employee_id'], 'resource_allocations_org_employee_idx');
            $table->index(['organization_id', 'project_id'], 'resource_allocations_org_project_idx');
            $table->index(
                ['organization_id', 'planned_start_date', 'planned_end_date'],
                'resource_allocations_org_dates_idx'
            );
        });

        Schema::create('workload_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('allocated_hours', 8, 2)->default(0);
            $table->decimal('available_hours', 8, 2)->default(0);
            $table->decimal('utilization_percentage', 8, 2)->default(0);
            $table->string('overall_status', 50);
            $table->timestamps();

            $table->unique(
                ['organization_id', 'employee_id', 'snapshot_date'],
                'workload_snapshots_org_employee_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workload_snapshots');
        Schema::dropIfExists('resource_allocations');
        Schema::dropIfExists('resource_calendars');
    }
};
