<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_touches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('marketing_sessions')->cascadeOnDelete();
            $table->timestamp('occurred_at')->useCurrent();
            $table->string('channel', 50)->nullable();
            $table->string('source')->nullable();
            $table->string('medium')->nullable();
            $table->string('campaign')->nullable();
            $table->string('content')->nullable();
            $table->string('term')->nullable();
            $table->string('landing_page', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->timestamps();

            $table->index(['session_id', 'occurred_at']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_touches');
    }
};
