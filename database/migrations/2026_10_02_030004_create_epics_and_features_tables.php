<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('delivery_project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('backlog');
            $table->timestamps();
        });

        Schema::create('features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('delivery_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('epic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('acceptance_criteria')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('backlog');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
        Schema::dropIfExists('epics');
    }
};
