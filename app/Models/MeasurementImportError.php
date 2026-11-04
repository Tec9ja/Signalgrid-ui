<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class MeasurementImportError extends Model{protected $fillable=['measurement_import_id','row_number','row_data','errors'];protected function casts():array{return ['row_data'=>'array','errors'=>'array'];}public function import(){return $this->belongsTo(MeasurementImport::class,'measurement_import_id');}}
