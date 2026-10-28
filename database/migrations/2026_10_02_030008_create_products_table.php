<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->string('type')->default('app');
            $table->text('description')->nullable();
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('delivery_project_product', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('delivery_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['delivery_project_id', 'product_id']);
        });

        Schema::create('product_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('feature_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_feature');
        Schema::dropIfExists('delivery_project_product');
        Schema::dropIfExists('products');
    }
};
