<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_project_milestones', function (Blueprint $table) {
            $table->string('source_type')->default('manual')->after('delivery_project_id');
            $table->uuid('source_id')->nullable()->after('source_type');

            $table->unique(
                ['delivery_project_id', 'source_type', 'source_id'],
                'dpm_project_source_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('delivery_project_milestones', function (Blueprint $table) {
            $table->dropUnique('dpm_project_source_unique');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
