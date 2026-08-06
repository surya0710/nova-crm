<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant-owned integration fields (architectural correction).
 *
 * Platform app credentials stay in .env. Tenant secrets/config stay in DB.
 * No provider-specific columns — configuration JSON holds future account IDs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_providers', function (Blueprint $table) {
            $table->timestamp('disconnected_at')->nullable()->after('connected_at');
        });

        Schema::table('marketing_provider_credentials', function (Blueprint $table) {
            $table->json('configuration')->nullable()->after('scopes');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_provider_credentials', function (Blueprint $table) {
            $table->dropColumn('configuration');
        });

        Schema::table('marketing_providers', function (Blueprint $table) {
            $table->dropColumn('disconnected_at');
        });
    }
};
