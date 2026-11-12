<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('floor_plans', function (Blueprint $table) {
            $table->string('storage_disk', 32)->default('local');
        });

        Schema::table('measurement_imports', function (Blueprint $table) {
            $table->string('storage_disk', 32)->default('local');
        });

        Schema::table('report_exports', function (Blueprint $table) {
            $table->string('storage_disk', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('floor_plans', function (Blueprint $table) {
            $table->dropColumn('storage_disk');
        });

        Schema::table('measurement_imports', function (Blueprint $table) {
            $table->dropColumn('storage_disk');
        });

        Schema::table('report_exports', function (Blueprint $table) {
            $table->dropColumn('storage_disk');
        });
    }
};
