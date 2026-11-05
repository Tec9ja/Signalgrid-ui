<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class ActionItem extends Model{protected $fillable=['insight_id','assigned_to','created_by','title','description','status','due_date','completed_at'];protected function casts():array{return ['due_date'=>'date','completed_at'=>'datetime'];}public function insight(){return $this->belongsTo(Insight::class);}public function assignee(){return $this->belongsTo(User::class,'assigned_to');}}
