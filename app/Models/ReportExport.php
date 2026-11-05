<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;
class ReportExport extends Model{protected $fillable=['report_id','requested_by','format','status','file_name','file_path','file_size','error_message','completed_at'];protected function casts():array{return ['completed_at'=>'datetime'];}public function report(){return $this->belongsTo(Report::class);}public function requester(){return $this->belongsTo(User::class,'requested_by');}}
