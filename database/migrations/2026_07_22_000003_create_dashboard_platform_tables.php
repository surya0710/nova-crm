<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'slug'], 'dashboard_sections_org_slug_unique');
            $table->index(['organization_id', 'is_active', 'sort_order'], 'dashboard_sections_org_active_idx');
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('dashboard_sections')->cascadeOnDelete();
            $table->string('module', 50);
            $table->string('widget_key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('permission_slug')->nullable();
            $table->string('subscription_module', 50)->nullable();
            $table->unsignedTinyInteger('default_width')->default(6);
            $table->unsignedTinyInteger('default_height')->default(4);
            $table->unsignedInteger('default_position')->default(0);
            $table->string('data_provider');
            $table->json('configuration')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'widget_key'], 'dashboard_widgets_org_key_unique');
            $table->index(['organization_id', 'module', 'is_active'], 'dashboard_widgets_org_module_idx');
            $table->index(['section_id', 'default_position'], 'dashboard_widgets_section_position_idx');
        });

        Schema::create('organization_dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('widget_id')->constrained('dashboard_widgets')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'widget_id'], 'org_dashboard_widgets_unique');
            $table->index(['organization_id', 'is_enabled', 'sort_order'], 'org_dashboard_widgets_enabled_idx');
        });

        Schema::create('user_dashboard_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('widget_id')->constrained('dashboard_widgets')->cascadeOnDelete();
            $table->unsignedTinyInteger('position_x')->default(0);
            $table->unsignedTinyInteger('position_y')->default(0);
            $table->unsignedTinyInteger('width')->default(6);
            $table->unsignedTinyInteger('height')->default(4);
            $table->boolean('is_visible')->default(true);
            $table->json('custom_configuration')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id', 'widget_id'], 'user_dashboard_prefs_unique');
            $table->index(['organization_id', 'user_id', 'is_visible'], 'user_dashboard_prefs_visible_idx');
        });

        Schema::create('dashboard_quick_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('module', 50);
            $table->string('action_key');
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('route');
            $table->string('permission_slug')->nullable();
            $table->string('subscription_module', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'action_key'], 'dashboard_quick_actions_org_key_unique');
            $table->index(['organization_id', 'module', 'is_active'], 'dashboard_quick_actions_org_module_idx');
        });

        Schema::create('organization_quick_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quick_action_id')->constrained('dashboard_quick_actions')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'quick_action_id'], 'org_quick_actions_unique');
            $table->index(['organization_id', 'is_enabled', 'sort_order'], 'org_quick_actions_enabled_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_quick_actions');
        Schema::dropIfExists('dashboard_quick_actions');
        Schema::dropIfExists('user_dashboard_preferences');
        Schema::dropIfExists('organization_dashboard_widgets');
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboard_sections');
    }
};
