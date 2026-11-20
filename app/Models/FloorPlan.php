<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
class FloorPlan extends Model { use HasFactory; protected $fillable=['floor_id','uploaded_by','original_name','file_path','storage_disk','mime_type','file_size','original_width','original_height','pixels_per_metre','rotation_degrees','version','is_active','usable_boundary','calibration_x1']; protected function casts():array{return ['is_active'=>'boolean','usable_boundary'=>'array','pixels_per_metre'=>'decimal:4','rotation_degrees'=>'decimal:3','calibrated_at'=>'datetime','calibration_x1'=>'decimal:4','calibration_y1'=>'decimal:4','calibration_x2'=>'decimal:4','calibration_y2'=>'decimal:4','calibration_distance_metres'=>'decimal:4'];} public function floor(){return $this->belongsTo(Floor::class);} public function calibrator(){return $this->belongsTo(User::class,'calibrated_by');} public function uploader(){return $this->belongsTo(User::class,'uploaded_by');}  public function surveys(){return $this->hasMany(Survey::class);} public function storedFile(){return $this->hasOne(FloorPlanFile::class);}}
