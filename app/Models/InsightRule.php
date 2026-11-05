<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class InsightRule extends Model{protected $fillable=['code','name','category','severity','conditions','conclusion','recommendation','default_limitation','version','is_active'];protected function casts():array{return ['conditions'=>'array','is_active'=>'boolean'];}public function insights(){return $this->hasMany(Insight::class);}}
