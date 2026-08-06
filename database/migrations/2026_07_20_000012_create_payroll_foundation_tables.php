<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('component_type', 30);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('formula_supported')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'salary_components_org_id_unique');
            $table->unique(['organization_id', 'code'], 'salary_components_org_code_unique');
            $table->index(['organization_id', 'component_type'], 'salary_components_org_type_idx');
            $table->index(['organization_id', 'is_active'], 'salary_components_org_active_idx');
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'salary_structures_org_id_unique');
            $table->unique(['organization_id', 'name'], 'salary_structures_org_name_unique');
            $table->index(['organization_id', 'is_active'], 'salary_structures_org_active_idx');
            $table->index(['organization_id', 'effective_date'], 'salary_structures_org_effective_idx');
        });

        Schema::create('salary_structure_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('salary_structure_id');
            $table->unsignedBigInteger('salary_component_id');
            $table->string('calculation_type', 30)->default('fixed');
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('percentage', 8, 4)->nullable();
            $table->unsignedBigInteger('based_on_component_id')->nullable();
            $table->text('formula')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'salary_structure_components_org_id_unique');
            $table->unique(
                ['organization_id', 'salary_structure_id', 'salary_component_id'],
                'salary_structure_components_org_structure_component_unique'
            );
            $table->foreign(['organization_id', 'salary_structure_id'], 'ssc_org_structure_fk')
                ->references(['organization_id', 'id'])->on('salary_structures')->cascadeOnDelete();
            $table->foreign(['organization_id', 'salary_component_id'], 'ssc_org_component_fk')
                ->references(['organization_id', 'id'])->on('salary_components')->cascadeOnDelete();
            $table->foreign('based_on_component_id', 'ssc_based_on_fk')
                ->references('id')->on('salary_components')->nullOnDelete();
            $table->index(['organization_id', 'salary_structure_id'], 'ssc_org_structure_idx');
        });

        Schema::create('employee_salary_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('salary_structure_id');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->decimal('annual_ctc', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_salary_assignments_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'esa_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->foreign(['organization_id', 'salary_structure_id'], 'esa_org_structure_fk')
                ->references(['organization_id', 'id'])->on('salary_structures')->restrictOnDelete();
            $table->index(['organization_id', 'employee_id', 'effective_from'], 'esa_org_employee_from_idx');
            $table->index(['organization_id', 'employee_id', 'effective_until'], 'esa_org_employee_until_idx');
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'payroll_periods_org_id_unique');
            $table->unique(['organization_id', 'start_date', 'end_date'], 'payroll_periods_org_range_unique');
            $table->index(['organization_id', 'status'], 'payroll_periods_org_status_idx');
        });

        Schema::create('payroll_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('payroll_frequency', 30)->default('monthly');
            $table->string('currency', 10)->default('INR');
            $table->unsignedTinyInteger('working_days_per_month')->nullable();
            $table->json('week_off_days')->nullable();
            $table->string('overtime_handling', 50)->default('pay');
            $table->string('rounding_policy', 50)->default('nearest');
            $table->timestamps();

            $table->unique(['organization_id'], 'payroll_configurations_org_unique');
            $table->unique(['organization_id', 'id'], 'payroll_configurations_org_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_configurations');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('employee_salary_assignments');
        Schema::dropIfExists('salary_structure_components');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('salary_components');
    }
};
