<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('delivery_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('environment')->default('staging');
            $table->string('status')->default('planned')->index();
            $table->date('target_date')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('release_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('release_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');
            $table->uuid('item_id');
            $table->timestamps();
            $table->unique(['release_id', 'item_type', 'item_id']);
            $table->index(['item_type', 'item_id']);
        });

        Schema::create('deployments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('release_id')->constrained()->cascadeOnDelete();
            $table->string('environment');
            $table->string('status')->default('pending');
            $table->timestamp('deployed_at')->nullable();
            $table->foreignUuid('deployed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
        Schema::dropIfExists('release_items');
        Schema::dropIfExists('releases');
    }
};
