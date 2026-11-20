<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->unsignedBigInteger('floor_plan_id')->nullable()->change();
        });

        Schema::table('measurement_points', function (Blueprint $table) {
            $table->unsignedBigInteger('floor_plan_id')->nullable()->change();
            $table->decimal('x_normalized', 9, 6)->nullable()->change();
            $table->decimal('y_normalized', 9, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('measurement_points', function (Blueprint $table) {
            $table->decimal('y_normalized', 9, 6)->nullable(false)->change();
            $table->decimal('x_normalized', 9, 6)->nullable(false)->change();
            $table->unsignedBigInteger('floor_plan_id')->nullable(false)->change();
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->unsignedBigInteger('floor_plan_id')->nullable(false)->change();
        });
    }
};
