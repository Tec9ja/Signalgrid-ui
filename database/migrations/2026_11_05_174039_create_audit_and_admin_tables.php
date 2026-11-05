<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 80)->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('route_name')->nullable();
            $table->string('request_id', 64)->nullable()->index();
            $table->string('ip_hash', 128)->nullable();
            $table->string('user_agent_hash', 128)->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 80);
            $table->boolean('database_enabled')->default(true);
            $table->boolean('mail_enabled')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'event_key']);
        });

        Schema::create('system_checks', function (Blueprint $table) {
            $table->id();
            $table->string('check_key', 80)->unique();
            $table->string('status', 24)->index();
            $table->text('message');
            $table->jsonb('details')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_checks');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('audit_logs');
    }
};
