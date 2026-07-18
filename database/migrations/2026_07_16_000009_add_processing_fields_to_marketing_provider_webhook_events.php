<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_provider_webhook_events', function (Blueprint $table) {
            $table->text('failure_reason')->nullable()->after('processing_status');
            $table->unsignedInteger('processing_attempts')->default(0)->after('failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_provider_webhook_events', function (Blueprint $table) {
            $table->dropColumn(['failure_reason', 'processing_attempts']);
        });
    }
};
