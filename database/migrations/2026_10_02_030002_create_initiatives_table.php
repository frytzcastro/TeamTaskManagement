<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initiatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable()->unique();
            $table->foreignUuid('strategic_pillar_id')->constrained('strategic_pillars')->cascadeOnDelete();
            $table->string('name');
            $table->foreignUuid('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->string('priority')->default('medium');
            $table->string('status')->default('planned');
            $table->text('business_goal')->nullable();
            $table->text('problem')->nullable();
            $table->text('opportunity')->nullable();
            $table->text('expected_business_impact')->nullable();
            $table->text('success_criteria')->nullable();
            $table->text('dependencies')->nullable();
            $table->text('risks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initiatives');
    }
};
