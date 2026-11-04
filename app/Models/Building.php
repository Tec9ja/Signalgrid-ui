<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model { use HasFactory, SoftDeletes; protected $fillable=['campus_id','name','code','description','display_order','status']; protected function casts():array{return [];} public function campus(){return $this->belongsTo(Campus::class);} public function floors(){return $this->hasMany(Floor::class);} }
