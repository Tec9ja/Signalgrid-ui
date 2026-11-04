<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class SystemThreshold extends Model{protected $fillable=['key','value','unit','description','is_active'];protected function casts():array{return ['value'=>'decimal:4','is_active'=>'boolean'];}public static function number(string $key,float $default):float{return (float)(static::where('key',$key)->where('is_active',true)->value('value')??$default);}}
