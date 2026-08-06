<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permission_groups')) {
            Schema::create('permission_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->string('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['organization_id', 'slug']);
            });
        }

        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'permission_group_id')) {
                $table->foreignId('permission_group_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('permissions', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('permission_group_id')->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('permissions', 'is_system')) {
                $table->boolean('is_system')->default(true)->after('description');
            }
            if (! Schema::hasColumn('permissions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_system');
            }
        });

        $this->dropSlugUniqueIfExists();

        Schema::table('permissions', function (Blueprint $table) {
            if (! $this->indexExists('permissions', 'permissions_organization_id_slug_unique')) {
                $table->unique(['organization_id', 'slug']);
            }
        });

        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'color')) {
                $table->string('color', 20)->nullable()->after('description');
            }
            if (! Schema::hasColumn('roles', 'hierarchy_level')) {
                $table->unsignedTinyInteger('hierarchy_level')->default(0)->after('color');
            }
            if (! Schema::hasColumn('roles', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_system');
            }
            if (! Schema::hasColumn('roles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_default');
            }
        });

        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['organization_id', 'role_id', 'permission_id']);
            });
        }

        if (Schema::hasTable('role_permission') && Schema::hasTable('role_permissions')) {
            $rows = DB::table('role_permission')
                ->join('roles', 'roles.id', '=', 'role_permission.role_id')
                ->select('roles.organization_id', 'role_permission.role_id', 'role_permission.permission_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('role_permissions')->insertOrIgnore([
                    'organization_id' => $row->organization_id,
                    'role_id' => $row->role_id,
                    'permission_id' => $row->permission_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::drop('role_permission');
        }

        if (! Schema::hasTable('user_roles')) {
            Schema::create('user_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->useCurrent();
                $table->timestamps();

                $table->unique(['organization_id', 'role_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('permission_templates')) {
            Schema::create('permission_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permission_template_roles')) {
            Schema::create('permission_template_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('permission_template_id');
                $table->string('role_name');
                $table->string('role_slug');
                $table->string('role_description')->nullable();
                $table->unsignedTinyInteger('hierarchy_level')->default(0);
                $table->string('color', 20)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('permission_template_id', 'ptr_template_fk')
                    ->references('id')
                    ->on('permission_templates')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('permission_template_permissions')) {
            Schema::create('permission_template_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('permission_template_role_id');
                $table->string('permission_slug');
                $table->timestamps();

                $table->foreign('permission_template_role_id', 'ptp_template_role_fk')
                    ->references('id')
                    ->on('permission_template_roles')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_template_permissions');
        Schema::dropIfExists('permission_template_roles');
        Schema::dropIfExists('permission_templates');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');

        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'color')) {
                $table->dropColumn(['color', 'hierarchy_level', 'is_default', 'is_active']);
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            if ($this->indexExists('permissions', 'permissions_organization_id_slug_unique')) {
                $table->dropUnique(['organization_id', 'slug']);
            }
            if (! $this->indexExists('permissions', 'permissions_slug_unique')) {
                $table->unique(['slug']);
            }
            if (Schema::hasColumn('permissions', 'permission_group_id')) {
                $table->dropConstrainedForeignId('permission_group_id');
            }
            if (Schema::hasColumn('permissions', 'organization_id')) {
                $table->dropConstrainedForeignId('organization_id');
            }
            if (Schema::hasColumn('permissions', 'is_system')) {
                $table->dropColumn(['is_system', 'is_active']);
            }
        });

        Schema::dropIfExists('permission_groups');

        if (! Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->primary(['role_id', 'permission_id']);
            });
        }
    }

    protected function dropSlugUniqueIfExists(): void
    {
        if ($this->indexExists('permissions', 'permissions_slug_unique')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropUnique(['slug']);
            });
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) AS count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return ($result[0]->count ?? 0) > 0;
    }
};
