<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'calibration_x1' => fn (Blueprint $table) => $table->decimal('calibration_x1', 12, 4)->nullable(),
            'calibration_y1' => fn (Blueprint $table) => $table->decimal('calibration_y1', 12, 4)->nullable(),
            'calibration_x2' => fn (Blueprint $table) => $table->decimal('calibration_x2', 12, 4)->nullable(),
            'calibration_y2' => fn (Blueprint $table) => $table->decimal('calibration_y2', 12, 4)->nullable(),
            'calibration_distance_metres' => fn (Blueprint $table) => $table->decimal('calibration_distance_metres', 12, 4)->nullable(),
            'calibrated_at' => fn (Blueprint $table) => $table->timestamp('calibrated_at')->nullable(),
            'calibrated_by' => fn (Blueprint $table) => $table->unsignedBigInteger('calibrated_by')->nullable(),
            'processing_status' => fn (Blueprint $table) => $table->string('processing_status', 24)->default('ready'),
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('floor_plans', $name)) {
                Schema::table('floor_plans', $definition);
            }
        }
    }

    public function down(): void
    {
        // This is a compatibility migration. Drop only columns that exist.
        foreach ([
            'calibration_x1',
            'calibration_y1',
            'calibration_x2',
            'calibration_y2',
            'calibration_distance_metres',
            'calibrated_at',
            'calibrated_by',
            'processing_status',
        ] as $column) {
            if (Schema::hasColumn('floor_plans', $column)) {
                Schema::table('floor_plans', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
