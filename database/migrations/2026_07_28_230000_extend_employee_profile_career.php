<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_emergency_contacts', function (Blueprint $table) {
            $table->string('alternate_mobile', 50)->nullable()->after('phone');
            $table->text('address')->nullable()->after('email');
        });

        Schema::table('employee_educations', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('field_of_study');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('grade', 100)->nullable()->after('end_year');
            $table->text('description')->nullable()->after('grade');
        });

        Schema::table('employee_experiences', function (Blueprint $table) {
            $table->string('employment_type', 50)->nullable()->after('title');
            $table->text('technologies')->nullable()->after('end_date');
        });

        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('skill');
            $table->string('proficiency', 30)->default('intermediate');
            $table->unsignedTinyInteger('years_of_experience')->nullable();
            $table->date('last_used')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_skills_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_skills_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->index(['organization_id', 'employee_id'], 'employee_skills_org_employee_idx');
        });

        Schema::create('employee_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->string('name');
            $table->string('issuing_organization')->nullable();
            $table->string('credential_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('credential_url')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'id'], 'employee_certifications_org_id_unique');
            $table->foreign(['organization_id', 'employee_id'], 'employee_certifications_org_employee_fk')
                ->references(['organization_id', 'id'])->on('employees')->cascadeOnDelete();
            $table->index(['organization_id', 'employee_id'], 'employee_certifications_org_employee_idx');
            $table->index(['organization_id', 'expiry_date'], 'employee_certifications_org_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_certifications');
        Schema::dropIfExists('employee_skills');

        Schema::table('employee_experiences', function (Blueprint $table) {
            $table->dropColumn(['employment_type', 'technologies']);
        });

        Schema::table('employee_educations', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'grade', 'description']);
        });

        Schema::table('employee_emergency_contacts', function (Blueprint $table) {
            $table->dropColumn(['alternate_mobile', 'address']);
        });
    }
};
