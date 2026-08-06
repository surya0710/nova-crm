<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrms_branches', function (Blueprint $table) {
            $table->string('contact_person')->nullable()->after('country');
            $table->string('contact_email')->nullable()->after('contact_person');
            $table->string('contact_phone', 50)->nullable()->after('contact_email');
        });

        Schema::table('hrms_departments', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });

        Schema::table('hrms_designations', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('organization_id');
            $table->text('description')->nullable()->after('name');
            $table->foreign('department_id', 'hrms_designations_department_fk')
                ->references('id')->on('hrms_departments')->nullOnDelete();
        });

        Schema::table('hrms_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('team_lead_employee_id')->nullable()->after('department_id');
            $table->foreign('team_lead_employee_id', 'hrms_teams_team_lead_fk')
                ->references('id')->on('employees')->nullOnDelete();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('gender', 20)->nullable()->after('last_name');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('mobile', 50)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['gender', 'date_of_birth', 'mobile']);
        });

        Schema::table('hrms_teams', function (Blueprint $table) {
            $table->dropForeign('hrms_teams_team_lead_fk');
            $table->dropColumn('team_lead_employee_id');
        });

        Schema::table('hrms_designations', function (Blueprint $table) {
            $table->dropForeign('hrms_designations_department_fk');
            $table->dropColumn(['department_id', 'description']);
        });

        Schema::table('hrms_departments', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('hrms_branches', function (Blueprint $table) {
            $table->dropColumn(['contact_person', 'contact_email', 'contact_phone']);
        });
    }
};
