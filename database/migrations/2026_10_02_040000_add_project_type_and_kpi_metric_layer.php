<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_projects', function (Blueprint $table) {
            $table->string('type')->default('standard')->after('code')->index();
        });

        Schema::table('kpis', function (Blueprint $table) {
            $table->string('metric_layer')->default('business')->after('code')->index();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_projects', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('kpis', function (Blueprint $table) {
            $table->dropColumn('metric_layer');
        });
    }
};
