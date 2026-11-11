<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('website', 255)->nullable()->after('timezone');
            $table->string('motto', 255)->nullable()->after('website');
        });

        Schema::table('campuses', function (Blueprint $table) {
            $table->string('locality', 150)->nullable()->after('address');
            $table->string('country', 80)->nullable()->after('locality');
            $table->string('reference_source', 40)->nullable()->after('longitude');
            $table->timestamp('setup_completed_at')->nullable()->after('reference_source');
            $table->text('setup_note')->nullable()->after('setup_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['website', 'motto']);
        });

        Schema::table('campuses', function (Blueprint $table) {
            $table->dropColumn(['locality', 'country', 'reference_source', 'setup_completed_at', 'setup_note']);
        });
    }
};
