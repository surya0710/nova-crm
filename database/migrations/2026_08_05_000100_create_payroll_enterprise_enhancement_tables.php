<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payroll_configurations', 'salary_mode')) {
            Schema::table('payroll_configurations', function (Blueprint $table) {
                $table->string('salary_mode', 30)->default('calendar')->after('rounding_policy');
            });
        }
        if (! Schema::hasColumn('payroll_configurations', 'salary_credit_day')) {
            Schema::table('payroll_configurations', function (Blueprint $table) {
                $table->unsignedTinyInteger('salary_credit_day')->nullable();
            });
        }
        if (! Schema::hasColumn('payroll_configurations', 'auto_generate')
            && ! Schema::hasColumn('payroll_configurations', 'auto_generate_payroll')) {
            Schema::table('payroll_configurations', function (Blueprint $table) {
                $table->boolean('auto_generate')->default(false);
            });
        }
        if (! Schema::hasColumn('payroll_configurations', 'reminder_days_before_credit')) {
            Schema::table('payroll_configurations', function (Blueprint $table) {
                $table->unsignedSmallInteger('reminder_days_before_credit')->nullable();
            });
        }

        if (! Schema::hasColumn('payroll_runs', 'payment_reference')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->string('payment_reference', 120)->nullable()->after('engine_version');
            });
        }
        if (! Schema::hasColumn('payroll_runs', 'payment_date')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->date('payment_date')->nullable();
            });
        }
        if (! Schema::hasColumn('payroll_runs', 'paid_at')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->timestamp('paid_at')->nullable();
            });
        }
        if (! Schema::hasColumn('payroll_runs', 'paid_by')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('payroll_runs', 'payment_notes')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->text('payment_notes')->nullable();
            });
        }
        if (! Schema::hasColumn('payroll_runs', 'custom_fields')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->json('custom_fields')->nullable();
            });
        }

        if (! Schema::hasTable('payroll_adjustments')) {
            Schema::create('payroll_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('payroll_period_id')->nullable();
                $table->unsignedBigInteger('payroll_run_id')->nullable();
                $table->string('adjustment_number', 60);
                $table->string('adjustment_type', 40);
                $table->string('direction', 20)->default('earning');
                $table->decimal('amount', 15, 2);
                $table->string('status', 30)->default('draft');
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('effective_date')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->json('meta')->nullable();
                $table->json('custom_fields')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['organization_id', 'id'], 'payroll_adjustments_org_id_unique');
                $table->unique(['organization_id', 'adjustment_number'], 'payroll_adjustments_org_number_unique');
                $table->foreign('employee_id', 'payroll_adjustments_employee_fk')
                    ->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('payroll_period_id', 'payroll_adjustments_period_fk')
                    ->references('id')->on('payroll_periods')->nullOnDelete();
                $table->foreign('payroll_run_id', 'payroll_adjustments_run_fk')
                    ->references('id')->on('payroll_runs')->nullOnDelete();
                $table->index(['organization_id', 'employee_id', 'status'], 'payroll_adjustments_org_emp_status_idx');
                $table->index(['organization_id', 'payroll_period_id', 'status'], 'payroll_adjustments_org_period_status_idx');
                $table->index(['organization_id', 'adjustment_type'], 'payroll_adjustments_org_type_idx');
            });
        }

        if (! Schema::hasColumn('employee_salary_assignments', 'custom_fields')) {
            Schema::table('employee_salary_assignments', function (Blueprint $table) {
                $table->json('custom_fields')->nullable()->after('notes');
            });
        }

        if (! Schema::hasColumn('payslips', 'custom_fields')) {
            Schema::table('payslips', function (Blueprint $table) {
                $table->json('custom_fields')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};
