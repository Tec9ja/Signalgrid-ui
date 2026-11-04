<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model { use HasFactory, SoftDeletes; protected $fillable=['institution_id','name','code','address','latitude','longitude','status']; protected function casts():array{return ['latitude'=>'decimal:7','longitude'=>'decimal:7'];} public function institution(){return $this->belongsTo(Institution::class);} public function buildings(){return $this->hasMany(Building::class);} }
