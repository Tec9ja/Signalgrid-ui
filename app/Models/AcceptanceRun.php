<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class AcceptanceRun extends Model{protected $fillable=['executed_by','status','passed_checks','failed_checks','checks','environment','executed_at'];protected function casts():array{return ['checks'=>'array','environment'=>'array','executed_at'=>'datetime'];}}
