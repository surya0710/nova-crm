<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('resource_allocations')) {
            return;
        }

        if (! Schema::hasColumn('resource_allocations', 'metadata')) {
            Schema::table('resource_allocations', function (Blueprint $table) {
                $table->json('metadata')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('resource_allocations') && Schema::hasColumn('resource_allocations', 'metadata')) {
            Schema::table('resource_allocations', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }
};
