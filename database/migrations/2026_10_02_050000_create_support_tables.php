<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_slas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('priority')->unique();
            $table->unsignedInteger('response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('other');
            $table->string('priority')->default('medium')->index();
            $table->string('status')->default('open')->index();
            $table->foreignUuid('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('delivery_project_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolve_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('converted_bug_id')->nullable()->constrained('bugs')->nullOnDelete();
            $table->foreignUuid('converted_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignUuid('converted_feature_id')->nullable()->constrained('features')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('support_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_comments');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('support_slas');
    }
};
