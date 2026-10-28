<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable()->unique();
            $table->foreignUuid('initiative_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignUuid('project_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('product_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('team_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('target_completion')->nullable();
            $table->date('actual_completion')->nullable();
            $table->string('status')->default('planned');
            $table->string('health')->default('green');
            $table->string('priority')->default('medium');
            $table->text('description')->nullable();
            $table->text('success_criteria')->nullable();
            $table->text('risks')->nullable();
            $table->text('dependencies')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_project_milestones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('delivery_project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('delivery_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['delivery_project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_project_members');
        Schema::dropIfExists('delivery_project_milestones');
        Schema::dropIfExists('delivery_projects');
    }
};
