<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurveyArea extends Model { use HasFactory, SoftDeletes; protected $fillable=['floor_id','name','code','area_type','boundary_points','area_sqm','status']; protected function casts():array{return ['boundary_points'=>'array','area_sqm'=>'decimal:2'];} public function floor(){return $this->belongsTo(Floor::class);} }
