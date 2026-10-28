<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_project_product', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('product_feature', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('bugs', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('releases', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::rename('products', 'systems');
        Schema::rename('delivery_project_product', 'delivery_project_system');
        Schema::rename('product_feature', 'system_feature');

        Schema::table('delivery_project_system', function (Blueprint $table) {
            $table->renameColumn('product_id', 'system_id');
        });
        Schema::table('system_feature', function (Blueprint $table) {
            $table->renameColumn('product_id', 'system_id');
        });
        Schema::table('bugs', function (Blueprint $table) {
            $table->renameColumn('product_id', 'system_id');
        });
        Schema::table('releases', function (Blueprint $table) {
            $table->renameColumn('product_id', 'system_id');
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->renameColumn('product_id', 'system_id');
        });

        Schema::table('delivery_project_system', function (Blueprint $table) {
            $table->foreign('system_id')->references('id')->on('systems')->cascadeOnDelete();
        });
        Schema::table('system_feature', function (Blueprint $table) {
            $table->foreign('system_id')->references('id')->on('systems')->cascadeOnDelete();
        });
        Schema::table('bugs', function (Blueprint $table) {
            $table->foreign('system_id')->references('id')->on('systems')->nullOnDelete();
        });
        Schema::table('releases', function (Blueprint $table) {
            $table->foreign('system_id')->references('id')->on('systems')->nullOnDelete();
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreign('system_id')->references('id')->on('systems')->nullOnDelete();
        });

        foreach (DB::table('systems')->where('code', 'like', 'PRD-%')->get() as $row) {
            DB::table('systems')
                ->where('id', $row->id)
                ->update(['code' => 'SYS-'.substr($row->code, 4)]);
        }

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->where('subject_type', 'App\\Models\\Product')
                ->update(['subject_type' => 'App\\Models\\System']);
        }

        $this->renamePermissions();
    }

    public function down(): void
    {
        $this->revertPermissions();

        if (Schema::hasTable('activity_log')) {
            DB::table('activity_log')
                ->where('subject_type', 'App\\Models\\System')
                ->update(['subject_type' => 'App\\Models\\Product']);
        }

        foreach (DB::table('systems')->where('code', 'like', 'SYS-%')->get() as $row) {
            DB::table('systems')
                ->where('id', $row->id)
                ->update(['code' => 'PRD-'.substr($row->code, 4)]);
        }

        Schema::table('delivery_project_system', function (Blueprint $table) {
            $table->dropForeign(['system_id']);
        });
        Schema::table('system_feature', function (Blueprint $table) {
            $table->dropForeign(['system_id']);
        });
        Schema::table('bugs', function (Blueprint $table) {
            $table->dropForeign(['system_id']);
        });
        Schema::table('releases', function (Blueprint $table) {
            $table->dropForeign(['system_id']);
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropForeign(['system_id']);
        });

        Schema::table('delivery_project_system', function (Blueprint $table) {
            $table->renameColumn('system_id', 'product_id');
        });
        Schema::table('system_feature', function (Blueprint $table) {
            $table->renameColumn('system_id', 'product_id');
        });
        Schema::table('bugs', function (Blueprint $table) {
            $table->renameColumn('system_id', 'product_id');
        });
        Schema::table('releases', function (Blueprint $table) {
            $table->renameColumn('system_id', 'product_id');
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->renameColumn('system_id', 'product_id');
        });

        Schema::rename('systems', 'products');
        Schema::rename('delivery_project_system', 'delivery_project_product');
        Schema::rename('system_feature', 'product_feature');

        Schema::table('delivery_project_product', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
        Schema::table('product_feature', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
        Schema::table('bugs', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
        Schema::table('releases', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    private function renamePermissions(): void
    {
        $map = [
            'products.view' => 'systems.view',
            'products.manage' => 'systems.manage',
        ];

        foreach ($map as $from => $to) {
            $new = Permission::findOrCreate($to);
            $old = Permission::query()->where('name', $from)->where('guard_name', 'web')->first();

            if (! $old) {
                continue;
            }

            foreach (Role::all() as $role) {
                if ($role->hasPermissionTo($old)) {
                    $role->givePermissionTo($new);
                    $role->revokePermissionTo($old);
                }
            }

            $old->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function revertPermissions(): void
    {
        $map = [
            'systems.view' => 'products.view',
            'systems.manage' => 'products.manage',
        ];

        foreach ($map as $from => $to) {
            $new = Permission::findOrCreate($to);
            $old = Permission::query()->where('name', $from)->where('guard_name', 'web')->first();

            if (! $old) {
                continue;
            }

            foreach (Role::all() as $role) {
                if ($role->hasPermissionTo($old)) {
                    $role->givePermissionTo($new);
                    $role->revokePermissionTo($old);
                }
            }

            $old->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
