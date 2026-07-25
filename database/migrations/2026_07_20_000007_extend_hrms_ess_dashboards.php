<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('personal_email')->nullable()->after('email');
            $table->string('address_line_1')->nullable()->after('mobile');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('state')->nullable()->after('city');
            $table->string('postal_code', 30)->nullable()->after('state');
            $table->string('country', 100)->nullable()->after('postal_code');
            $table->string('profile_photo_path')->nullable()->after('country');
        });

        Schema::table('hrms_announcements', function (Blueprint $table) {
            $table->string('target_audience', 30)->default('everyone')->after('body');
            $table->date('start_date')->nullable()->after('target_audience');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('hrms_announcements', function (Blueprint $table) {
            $table->dropColumn(['target_audience', 'start_date', 'end_date']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'personal_email',
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'postal_code',
                'country',
                'profile_photo_path',
            ]);
        });
    }
};
