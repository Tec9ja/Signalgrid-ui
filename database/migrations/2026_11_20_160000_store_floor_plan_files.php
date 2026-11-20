<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floor_plan_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_plan_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('contents');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floor_plan_files');
    }
};
