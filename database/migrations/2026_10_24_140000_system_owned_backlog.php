<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initiative_system', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('initiative_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('system_id')->constrained('systems')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['initiative_id', 'system_id']);
        });

        Schema::table('epics', function (Blueprint $table) {
            $table->uuid('system_id')->nullable()->after('id');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->uuid('system_id')->nullable()->after('id');
        });

        $fallbackSystemId = DB::table('systems')->orderBy('name')->value('id');
        if (! $fallbackSystemId) {
            $fallbackSystemId = (string) Str::uuid();
            DB::table('systems')->insert([
                'id' => $fallbackSystemId,
                'code' => 'SYS-MIGRATE',
                'name' => 'Unassigned System',
                'type' => 'app',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Initiative ← systems from delivery projects
        if (Schema::hasTable('delivery_project_system')) {
            $rows = DB::table('delivery_project_system')
                ->join('delivery_projects', 'delivery_projects.id', '=', 'delivery_project_system.delivery_project_id')
                ->whereNotNull('delivery_projects.initiative_id')
                ->select(
                    'delivery_projects.initiative_id',
                    'delivery_project_system.system_id',
                    DB::raw('MIN(delivery_project_system.created_at) as created_at'),
                    DB::raw('MAX(delivery_project_system.updated_at) as updated_at'),
                )
                ->groupBy('delivery_projects.initiative_id', 'delivery_project_system.system_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('initiative_system')->insertOrIgnore([
                    'initiative_id' => $row->initiative_id,
                    'system_id' => $row->system_id,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }
        }

        $projectPrimarySystem = [];
        if (Schema::hasTable('delivery_project_system')) {
            foreach (DB::table('delivery_project_system')->orderBy('id')->get() as $row) {
                $projectPrimarySystem[$row->delivery_project_id] ??= $row->system_id;
            }
        }

        foreach (DB::table('epics')->get() as $epic) {
            $systemId = $projectPrimarySystem[$epic->delivery_project_id] ?? $fallbackSystemId;
            DB::table('epics')->where('id', $epic->id)->update(['system_id' => $systemId]);
        }

        $featureSystems = [];
        if (Schema::hasTable('system_feature')) {
            foreach (DB::table('system_feature')->orderBy('id')->get() as $row) {
                $featureSystems[$row->feature_id] ??= $row->system_id;
            }
        }

        foreach (DB::table('features')->get() as $feature) {
            $systemId = $featureSystems[$feature->id]
                ?? ($feature->epic_id
                    ? DB::table('epics')->where('id', $feature->epic_id)->value('system_id')
                    : null)
                ?? ($projectPrimarySystem[$feature->delivery_project_id] ?? null)
                ?? $fallbackSystemId;
            DB::table('features')->where('id', $feature->id)->update(['system_id' => $systemId]);
        }

        Schema::table('epics', function (Blueprint $table) {
            $table->foreign('system_id')->references('id')->on('systems')->cascadeOnDelete();
        });
        Schema::table('features', function (Blueprint $table) {
            $table->foreign('system_id')->references('id')->on('systems')->cascadeOnDelete();
        });

        Schema::table('epics', function (Blueprint $table) {
            $table->dropForeign(['delivery_project_id']);
            $table->dropColumn('delivery_project_id');
        });

        $featureProjectIds = DB::table('features')->pluck('delivery_project_id', 'id');

        Schema::table('features', function (Blueprint $table) {
            $table->dropForeign(['delivery_project_id']);
            $table->dropColumn('delivery_project_id');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->foreignUuid('delivery_project_id')->nullable()->after('system_id')->constrained()->nullOnDelete();
        });

        foreach ($featureProjectIds as $featureId => $projectId) {
            DB::table('features')->where('id', $featureId)->update(['delivery_project_id' => $projectId]);
        }

        Schema::dropIfExists('delivery_project_system');
        Schema::dropIfExists('system_feature');
    }

    public function down(): void
    {
        Schema::create('delivery_project_system', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('delivery_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('system_id')->constrained('systems')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['delivery_project_id', 'system_id']);
        });

        Schema::create('system_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('system_id')->constrained('systems')->cascadeOnDelete();
            $table->foreignUuid('feature_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['system_id', 'feature_id']);
        });

        // Best-effort reverse: features with projects → project systems; features → system_feature
        foreach (DB::table('features')->whereNotNull('delivery_project_id')->whereNotNull('system_id')->get() as $feature) {
            DB::table('delivery_project_system')->insertOrIgnore([
                'delivery_project_id' => $feature->delivery_project_id,
                'system_id' => $feature->system_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('system_feature')->insertOrIgnore([
                'system_id' => $feature->system_id,
                'feature_id' => $feature->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('epics', function (Blueprint $table) {
            $table->uuid('delivery_project_id')->nullable()->after('id');
        });

        $fallbackProject = DB::table('delivery_projects')->orderBy('name')->value('id');
        foreach (DB::table('epics')->get() as $epic) {
            $projectId = DB::table('features')
                ->where('epic_id', $epic->id)
                ->whereNotNull('delivery_project_id')
                ->value('delivery_project_id')
                ?? $fallbackProject;
            DB::table('epics')->where('id', $epic->id)->update(['delivery_project_id' => $projectId]);
        }

        Schema::table('epics', function (Blueprint $table) {
            $table->foreign('delivery_project_id')->references('id')->on('delivery_projects')->cascadeOnDelete();
            $table->dropForeign(['system_id']);
            $table->dropColumn('system_id');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropForeign(['system_id']);
            $table->dropColumn('system_id');
            $table->dropForeign(['delivery_project_id']);
        });
        // leave delivery_project_id as-is (nullable) — original was required; skip force

        Schema::dropIfExists('initiative_system');
    }
};
