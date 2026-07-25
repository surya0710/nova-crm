<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_ui_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('theme', 20)->default('light');
            $table->string('density', 20)->default('comfortable');
            $table->boolean('sidebar_collapsed')->default(false);
            $table->string('last_workspace', 50)->nullable();
            $table->string('landing_page')->nullable();
            $table->json('favorites')->nullable();
            $table->json('pinned_pages')->nullable();
            $table->json('recent_pages')->nullable();
            $table->json('recent_searches')->nullable();
            $table->json('recent_commands')->nullable();
            $table->json('dashboard_layout')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id'], 'user_ui_prefs_org_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ui_preferences');
    }
};
