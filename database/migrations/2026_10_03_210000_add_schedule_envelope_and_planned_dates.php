<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epics', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status');
            $table->date('due_date')->nullable()->after('start_date');
            $table->date('rolled_start_date')->nullable()->after('due_date');
            $table->date('rolled_due_date')->nullable()->after('rolled_start_date');
        });

        Schema::table('features', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status');
            $table->date('due_date')->nullable()->after('start_date');
            $table->date('rolled_start_date')->nullable()->after('due_date');
            $table->date('rolled_due_date')->nullable()->after('rolled_start_date');
        });

        Schema::table('delivery_projects', function (Blueprint $table) {
            $table->date('rolled_start_date')->nullable()->after('target_completion');
            $table->date('rolled_due_date')->nullable()->after('rolled_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('epics', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'due_date', 'rolled_start_date', 'rolled_due_date']);
        });

        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'due_date', 'rolled_start_date', 'rolled_due_date']);
        });

        Schema::table('delivery_projects', function (Blueprint $table) {
            $table->dropColumn(['rolled_start_date', 'rolled_due_date']);
        });
    }
};
