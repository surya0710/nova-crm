<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hrms_shifts', function (Blueprint $table) {
            $table->unsignedSmallInteger('grace_period_minutes')->default(0)->after('break_minutes');
            $table->unsignedSmallInteger('minimum_working_minutes')->nullable()->after('working_hours');
            $table->unsignedSmallInteger('overtime_threshold_minutes')->nullable()->after('minimum_working_minutes');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('source', 30)->default('manual')->after('status');
            $table->unsignedInteger('working_minutes')->default(0)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('hrms_shifts', function (Blueprint $table) {
            $table->dropColumn(['grace_period_minutes', 'minimum_working_minutes', 'overtime_threshold_minutes']);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['source', 'working_minutes']);
        });
    }
};
