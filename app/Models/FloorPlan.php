<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
class FloorPlan extends Model { use HasFactory; protected $fillable=['floor_id','uploaded_by','original_name','file_path','mime_type','file_size','original_width','original_height','pixels_per_metre','rotation_degrees','version','is_active','usable_boundary']; protected function casts():array{return ['is_active'=>'boolean','usable_boundary'=>'array','pixels_per_metre'=>'decimal:4','rotation_degrees'=>'decimal:3'];} public function floor(){return $this->belongsTo(Floor::class);} public function uploader(){return $this->belongsTo(User::class,'uploaded_by');} }
