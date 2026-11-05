<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class ResearchObservation extends Model{protected $fillable=['survey_id','recorded_by','observation_type','device_name','device_platform','environmental_conditions','observation','observed_at'];protected function casts():array{return ['environmental_conditions'=>'array','observed_at'=>'datetime'];}}
