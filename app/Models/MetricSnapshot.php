<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class MetricSnapshot extends Model{protected $fillable=['survey_id','floor_id','scope_type','scope_id','period_start','period_end','filters','metrics','methodology','calculation_version','calculated_at'];protected function casts():array{return ['period_start'=>'datetime','period_end'=>'datetime','filters'=>'array','metrics'=>'array','methodology'=>'array','calculated_at'=>'datetime'];}public function survey(){return $this->belongsTo(Survey::class);}public function floor(){return $this->belongsTo(Floor::class);}}
