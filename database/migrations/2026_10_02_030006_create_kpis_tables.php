<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->text('definition')->nullable();
            $table->text('formula')->nullable();
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('data_source')->nullable();
            $table->decimal('baseline', 12, 4)->nullable();
            $table->decimal('target', 12, 4)->nullable();
            $table->decimal('current_value', 12, 4)->nullable();
            $table->string('reporting_period')->nullable();
            $table->string('direction')->default('lower_is_better');
            $table->string('status')->default('no_data');
            $table->string('trend')->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();
        });

        Schema::create('initiative_kpi', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('initiative_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('kpi_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['initiative_id', 'kpi_id']);
        });

        Schema::create('delivery_project_kpi', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('delivery_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('kpi_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['delivery_project_id', 'kpi_id']);
        });

        Schema::create('kpi_measurements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kpi_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 12, 4);
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_measurements');
        Schema::dropIfExists('delivery_project_kpi');
        Schema::dropIfExists('initiative_kpi');
        Schema::dropIfExists('kpis');
    }
};
