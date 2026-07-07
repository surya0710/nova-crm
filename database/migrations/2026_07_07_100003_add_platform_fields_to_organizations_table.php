<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_active');
            $table->string('plan')->default('starter')->after('status');
            $table->timestamp('last_activity_at')->nullable()->after('plan');
            $table->timestamp('archived_at')->nullable()->after('last_activity_at');
            $table->unsignedBigInteger('storage_used_bytes')->default(0)->after('archived_at');
        });

        DB::table('organizations')
            ->where('is_active', false)
            ->update(['status' => 'suspended']);

        DB::table('organizations')
            ->where('is_active', true)
            ->whereNull('status')
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['status', 'plan', 'last_activity_at', 'archived_at', 'storage_used_bytes']);
        });
    }
};
