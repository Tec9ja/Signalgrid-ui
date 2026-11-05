<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class ProjectProfile extends Model{protected $fillable=['institution_id','project_title','aim','objectives','scope','limitations','methodology','specification_version','is_active'];protected function casts():array{return ['objectives'=>'array','scope'=>'array','limitations'=>'array','methodology'=>'array','is_active'=>'boolean'];}public function institution(){return $this->belongsTo(Institution::class);}}
