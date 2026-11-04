<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model { use HasFactory, SoftDeletes; protected $fillable=['building_id','name','level_number','code','area_sqm','display_order','status']; protected function casts():array{return ['area_sqm'=>'decimal:2'];} public function building(){return $this->belongsTo(Building::class);} public function surveyAreas(){return $this->hasMany(SurveyArea::class);} public function floorPlans(){return $this->hasMany(FloorPlan::class);} public function activeFloorPlan(){return $this->hasOne(FloorPlan::class)->where('is_active',true)->latestOfMany();} }
