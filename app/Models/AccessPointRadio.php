<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class AccessPointRadio extends Model{protected $fillable=['access_point_id','band','channel','channel_width_mhz','tx_power_dbm','radio_identifier'];public function accessPoint(){return $this->belongsTo(AccessPoint::class);}public function loadSamples(){return $this->hasMany(ApLoadSample::class);}}
