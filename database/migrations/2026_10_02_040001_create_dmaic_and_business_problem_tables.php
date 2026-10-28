<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_problems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('initiative_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('problem_statement')->nullable();
            $table->text('business_impact')->nullable();
            $table->text('customer_impact')->nullable();
            $table->text('opportunity')->nullable();
            $table->string('status')->default('open');
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dmaic_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('delivery_project_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('business_problem_id')->nullable()->constrained()->nullOnDelete();
            $table->string('current_phase')->default('DEFINE');
            $table->string('status')->default('active');
            $table->foreignUuid('sponsor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('process_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dmaic_phases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_profile_id')->constrained()->cascadeOnDelete();
            $table->string('phase');
            $table->text('objectives')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['dmaic_profile_id', 'phase']);
        });

        Schema::create('dmaic_gates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_phase_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('dmaic_phase_deliverables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_phase_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->boolean('required')->default(true);
            $table->boolean('completed')->default(false);
            $table->json('value')->nullable();
            $table->string('evidence_url')->nullable();
            $table->timestamps();

            $table->unique(['dmaic_phase_id', 'key']);
        });

        Schema::create('dmaic_measurement_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_profile_id')->constrained()->cascadeOnDelete();
            $table->string('metric_name');
            $table->text('definition')->nullable();
            $table->string('formula')->nullable();
            $table->string('data_source')->nullable();
            $table->string('frequency')->nullable();
            $table->foreignUuid('data_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('collection_method')->nullable();
            $table->unsignedInteger('sample_size')->nullable();
            $table->string('data_quality_status')->nullable();
            $table->decimal('baseline_value', 12, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('dmaic_measurements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_measurement_plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 12, 4);
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dmaic_root_causes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('method')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('suspected');
            $table->timestamps();
        });

        Schema::create('dmaic_root_cause_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_root_cause_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('evidence_url')->nullable();
            $table->foreignUuid('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dmaic_improvements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('dmaic_root_cause_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('feature_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('expected_impact')->nullable();
            $table->decimal('expected_cost', 12, 2)->nullable();
            $table->decimal('expected_benefit', 12, 2)->nullable();
            $table->string('status')->default('planned');
            $table->boolean('is_pilot')->default(false);
            $table->decimal('before_measurement', 12, 4)->nullable();
            $table->decimal('expected_measurement', 12, 4)->nullable();
            $table->decimal('after_measurement', 12, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('dmaic_control_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('dmaic_control_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_control_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('kpi_id')->nullable()->constrained()->nullOnDelete();
            $table->string('metric_name');
            $table->decimal('baseline', 12, 4)->nullable();
            $table->decimal('target', 12, 4)->nullable();
            $table->decimal('current_value', 12, 4)->nullable();
            $table->string('frequency')->nullable();
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('data_source')->nullable();
            $table->decimal('ucl', 12, 4)->nullable();
            $table->decimal('lcl', 12, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('dmaic_control_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dmaic_control_metric_id')->constrained()->cascadeOnDelete();
            $table->string('severity')->default('medium');
            $table->string('message');
            $table->string('status')->default('open');
            $table->timestamp('triggered_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('bugs', function (Blueprint $table) {
            $table->foreignUuid('dmaic_profile_id')->nullable()->after('task_id')->constrained()->nullOnDelete();
            $table->foreignUuid('dmaic_root_cause_id')->nullable()->after('dmaic_profile_id')->constrained()->nullOnDelete();
            $table->foreignUuid('dmaic_improvement_id')->nullable()->after('dmaic_root_cause_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dmaic_improvement_id');
            $table->dropConstrainedForeignId('dmaic_root_cause_id');
            $table->dropConstrainedForeignId('dmaic_profile_id');
        });

        Schema::dropIfExists('dmaic_control_alerts');
        Schema::dropIfExists('dmaic_control_metrics');
        Schema::dropIfExists('dmaic_control_plans');
        Schema::dropIfExists('dmaic_improvements');
        Schema::dropIfExists('dmaic_root_cause_evidence');
        Schema::dropIfExists('dmaic_root_causes');
        Schema::dropIfExists('dmaic_measurements');
        Schema::dropIfExists('dmaic_measurement_plans');
        Schema::dropIfExists('dmaic_phase_deliverables');
        Schema::dropIfExists('dmaic_gates');
        Schema::dropIfExists('dmaic_phases');
        Schema::dropIfExists('dmaic_profiles');
        Schema::dropIfExists('business_problems');
    }
};
