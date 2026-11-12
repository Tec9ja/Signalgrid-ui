<?php
namespace App\Services;use App\Models\{InsightRule,ProjectProfile,SystemThreshold};use Illuminate\Support\Facades\Schema;
class ProjectAlignmentAuditService{public function audit():array{$profile=ProjectProfile::where('is_active',true)->with('institution')->first();$checks=[
 ['key'=>'institution','passed'=>$profile?->institution?->name==='Adekunle Ajasin University, Akungba','detail'=>$profile?->institution?->name??'Missing'],
 ['key'=>'aim','passed'=>str_contains(strtolower((string)$profile?->aim),'rf heat')&&str_contains(strtolower((string)$profile?->aim),'load'),'detail'=>$profile?->aim??'Missing'],
 ['key'=>'location_hierarchy','passed'=>Schema::hasTable('campuses')&&Schema::hasTable('buildings')&&Schema::hasTable('floors')&&Schema::hasTable('survey_areas'),'detail'=>'Campus → Building → Floor → Survey Area'],
 ['key'=>'rf_heatmapping','passed'=>Schema::hasTable('heatmap_runs'),'detail'=>'IDW v1.1 with measured, imported and estimated values kept distinct'],
 ['key'=>'load_analysis','passed'=>Schema::hasTable('access_points')&&Schema::hasTable('ap_load_samples'),'detail'=>'Client count and channel utilisation'],
 ['key'=>'user_feedback','passed'=>Schema::hasTable('feedback_responses')&&Schema::hasTable('feedback_correlations'),'detail'=>'User reports matched to nearby technical evidence. A match is not a statistical correlation'],
 ['key'=>'explainable_rules','passed'=>InsightRule::whereIn('code',['RF-001','PERF-001','UX-001'])->count()===3,'detail'=>'RF-001, PERF-001, UX-001'],
 ['key'=>'thresholds','passed'=>SystemThreshold::whereIn('key',['rssi_weak_dbm','throughput_good_mbps','latency_good_ms','packet_loss_good_percent'])->count()===4,'detail'=>'Documented configurable project thresholds'],
 ['key'=>'provenance','passed'=>Schema::hasColumn('measurements','source_type')&&Schema::hasColumn('measurements','is_simulated'),'detail'=>'Measured, imported and simulated values remain distinct'],
 ['key'=>'comparisons','passed'=>Schema::hasTable('comparison_runs'),'detail'=>'Before-and-after survey comparisons']];return['profile'=>$profile,'checks'=>$checks,'passed'=>collect($checks)->where('passed',true)->count(),'total'=>count($checks),'aligned'=>collect($checks)->every(fn($c)=>$c['passed'])];}}
