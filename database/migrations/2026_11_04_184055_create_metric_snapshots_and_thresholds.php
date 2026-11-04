<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{
Schema::create('system_thresholds',function(Blueprint $t){$t->id();$t->string('key')->unique();$t->decimal('value',12,4);$t->string('unit',20);$t->text('description')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();});
Schema::create('metric_snapshots',function(Blueprint $t){$t->id();$t->foreignId('survey_id')->nullable()->constrained()->cascadeOnDelete();$t->foreignId('floor_id')->nullable()->constrained()->cascadeOnDelete();$t->string('scope_type',30);$t->unsignedBigInteger('scope_id')->nullable();$t->timestamp('period_start');$t->timestamp('period_end');$t->jsonb('filters')->nullable();$t->jsonb('metrics');$t->jsonb('methodology');$t->string('calculation_version',20)->default('1.0');$t->timestamp('calculated_at');$t->timestamps();$t->index(['scope_type','scope_id','period_start','period_end'],'metric_scope_period_idx');});
}public function down():void{Schema::dropIfExists('metric_snapshots');Schema::dropIfExists('system_thresholds');}};
