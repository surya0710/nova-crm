<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_rule_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('jurisdiction', 10)->default('IN');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'statutory_rule_sets_org_id_unique');
            $table->unique(['organization_id', 'code'], 'statutory_rule_sets_org_code_unique');
            $table->index(['organization_id', 'is_active'], 'statutory_rule_sets_org_active_idx');
            $table->index(['organization_id', 'jurisdiction'], 'statutory_rule_sets_org_jurisdiction_idx');
        });

        Schema::create('statutory_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('statutory_rule_set_id');
            $table->string('version', 40);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('jurisdiction', 10)->default('IN');
            $table->json('configuration');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'statutory_rule_versions_org_id_unique');
            $table->unique(
                ['organization_id', 'statutory_rule_set_id', 'version'],
                'statutory_rule_versions_org_set_version_unique'
            );
            $table->foreign(['organization_id', 'statutory_rule_set_id'], 'statutory_rule_versions_org_set_fk')
                ->references(['organization_id', 'id'])->on('statutory_rule_sets')->cascadeOnDelete();
            $table->index(
                ['organization_id', 'statutory_rule_set_id', 'effective_from'],
                'statutory_rule_versions_org_set_from_idx'
            );
        });

        Schema::create('employee_statutory_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->boolean('pf_eligible')->default(false);
            $table->string('pf_uan', 20)->nullable();
            $table->boolean('esi_eligible')->default(false);
            $table->string('esi_number', 30)->nullable();
            $table->string('professional_tax_state', 10)->nullable();
            $table->string('tax_regime', 20)->nullable();
            $table->string('pan', 20)->nullable();
            $table->string('aadhaar', 20)->nullable();
            $table->string('tan_reference', 30)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'id'], 'employee_statutory_profiles_org_id_unique');
            $table->unique(['organization_id', 'employee_id'], 'employee_statutory_profiles_org_employee_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_statutory_profiles_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
        });

        Schema::create('statutory_compliance_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('payroll_run_id')->nullable();
            $table->unsignedBigInteger('payroll_result_id')->nullable();
            $table->unsignedBigInteger('statutory_rule_set_id')->nullable();
            $table->unsignedBigInteger('statutory_rule_version_id')->nullable();
            $table->string('code', 80);
            $table->string('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'statutory_compliance_errors_org_id_unique');
            $table->foreign('employee_id', 'statutory_compliance_errors_employee_fk')
                ->references('id')->on('employees')->nullOnDelete();
            $table->foreign('payroll_run_id', 'statutory_compliance_errors_run_fk')
                ->references('id')->on('payroll_runs')->nullOnDelete();
            $table->foreign('payroll_result_id', 'statutory_compliance_errors_result_fk')
                ->references('id')->on('payroll_results')->nullOnDelete();
            $table->foreign('statutory_rule_set_id', 'statutory_compliance_errors_rule_set_fk')
                ->references('id')->on('statutory_rule_sets')->nullOnDelete();
            $table->foreign('statutory_rule_version_id', 'statutory_compliance_errors_rule_version_fk')
                ->references('id')->on('statutory_rule_versions')->nullOnDelete();
            $table->index(['organization_id', 'code'], 'statutory_compliance_errors_org_code_idx');
            $table->index(['organization_id', 'employee_id'], 'statutory_compliance_errors_org_employee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_compliance_errors');
        Schema::dropIfExists('employee_statutory_profiles');
        Schema::dropIfExists('statutory_rule_versions');
        Schema::dropIfExists('statutory_rule_sets');
    }
};
