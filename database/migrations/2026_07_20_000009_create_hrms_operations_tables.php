<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('asset_code');
            $table->string('name');
            $table->string('category', 50);
            $table->string('serial_number')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->date('assigned_date')->nullable();
            $table->date('return_date')->nullable();
            $table->string('status', 30)->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'employee_assets_org_id_unique');
            $table->unique(['organization_id', 'asset_code'], 'employee_assets_org_code_unique');
            $table->foreign('employee_id', 'employee_assets_employee_fk')
                ->references('id')->on('employees')->nullOnDelete();
            $table->index(['organization_id', 'status'], 'employee_assets_org_status_idx');
            $table->index(['organization_id', 'category'], 'employee_assets_org_category_idx');
        });

        Schema::create('employee_asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_asset_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('assigned_date');
            $table->date('return_date')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_asset_assignments_org_id_unique');
            $table->foreign(['organization_id', 'employee_asset_id'], 'employee_asset_assignments_org_asset_fk')
                ->references(['organization_id', 'id'])->on('employee_assets')->cascadeOnDelete();
            $table->foreign(['organization_id', 'employee_id'], 'employee_asset_assignments_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->index(['organization_id', 'employee_asset_id'], 'employee_asset_assignments_org_asset_idx');
            $table->index(['organization_id', 'employee_id'], 'employee_asset_assignments_org_employee_idx');
        });

        Schema::create('employee_exit_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('exit_type', 30);
            $table->date('last_working_day');
            $table->text('reason')->nullable();
            $table->text('exit_interview')->nullable();
            $table->text('hr_notes')->nullable();
            $table->text('manager_notes')->nullable();
            $table->string('status', 30)->default('in_progress');
            $table->boolean('checklist_assets_returned')->default(false);
            $table->boolean('checklist_documents_completed')->default(false);
            $table->boolean('checklist_knowledge_transfer')->default(false);
            $table->boolean('checklist_manager_approval')->default(false);
            $table->boolean('checklist_hr_approval')->default(false);
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'employee_exit_processes_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_exit_processes_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->index(['organization_id', 'status'], 'employee_exit_processes_org_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_exit_processes');
        Schema::dropIfExists('employee_asset_assignments');
        Schema::dropIfExists('employee_assets');
    }
};
