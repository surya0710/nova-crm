<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_period_id');
            $table->string('status', 30)->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('employee_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->string('engine_version', 20)->default('10.3.2');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'payroll_runs_org_id_unique');
            $table->foreign(['organization_id', 'payroll_period_id'], 'payroll_runs_org_period_fk')
                ->references(['organization_id', 'id'])->on('payroll_periods')->restrictOnDelete();
            $table->index(['organization_id', 'status'], 'payroll_runs_org_status_idx');
            $table->index(['organization_id', 'payroll_period_id'], 'payroll_runs_org_period_idx');
        });

        Schema::create('payroll_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_run_id');
            $table->unsignedBigInteger('employee_id');
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->decimal('working_days', 8, 2)->default(0);
            $table->decimal('payable_days', 8, 2)->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->decimal('overtime_amount', 15, 2)->default(0);
            $table->json('snapshot');
            $table->string('calculation_hash', 64);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'payroll_results_org_id_unique');
            $table->unique(['organization_id', 'payroll_run_id', 'employee_id'], 'payroll_results_org_run_employee_unique');
            $table->foreign(['organization_id', 'payroll_run_id'], 'payroll_results_org_run_fk')
                ->references(['organization_id', 'id'])->on('payroll_runs')->cascadeOnDelete();
            $table->foreign(['organization_id', 'employee_id'], 'payroll_results_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->restrictOnDelete();
            $table->index(['organization_id', 'payroll_run_id'], 'payroll_results_org_run_idx');
            $table->index(['organization_id', 'employee_id'], 'payroll_results_org_employee_idx');
        });

        Schema::create('payroll_validation_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('payroll_run_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('code', 80);
            $table->string('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'payroll_validation_errors_org_id_unique');
            $table->foreign(['organization_id', 'payroll_run_id'], 'payroll_validation_errors_org_run_fk')
                ->references(['organization_id', 'id'])->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('employee_id', 'payroll_validation_errors_employee_fk')
                ->references('id')->on('employees')->nullOnDelete();
            $table->index(['organization_id', 'payroll_run_id'], 'payroll_validation_errors_org_run_idx');
            $table->index(['organization_id', 'code'], 'payroll_validation_errors_org_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_validation_errors');
        Schema::dropIfExists('payroll_results');
        Schema::dropIfExists('payroll_runs');
    }
};
