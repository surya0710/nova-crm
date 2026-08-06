<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('marketing_visitors')->cascadeOnDelete();
            $table->uuid('session_uuid')->unique();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->string('landing_page', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->timestamps();

            $table->index('started_at');
            // Active-session lookup: latest open session per visitor.
            $table->index(['visitor_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_sessions');
    }
};
