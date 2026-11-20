<?php
namespace App\Http\Controllers;use App\Models\Floor;use App\Models\Survey;use App\Services\SurveyLifecycleService;use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;use Illuminate\View\View;
class SurveyController extends Controller{
 public function index(Request $r):View{
  abort_unless($r->user()->can('dashboard.view'),403);
  $surveys=Survey::with(['floor.building','floorPlan','surveyArea'])->withCount('points')->latest()->paginate(20);
  $floors=Floor::with(['building','floorPlans','surveyAreas'])->orderBy('name')->get();
  $surveyCreateFloors=$floors->map(fn(Floor $floor)=>[
   'id'=>$floor->id,
   'plans'=>$floor->floorPlans->map(fn($plan)=>['id'=>$plan->id,'version'=>$plan->version,'name'=>$plan->original_name,'active'=>(bool)$plan->is_active])->values(),
   'areas'=>$floor->surveyAreas->map(fn($area)=>['id'=>$area->id,'name'=>$area->name])->values(),
  ])->values();
  return view('surveys.index',compact('surveys','floors','surveyCreateFloors'));
 }
 public function store(Request $r):JsonResponse{abort_unless($r->user()->can('surveys.manage'),403);$d=$r->validate(['floor_id'=>'required|exists:floors,id','floor_plan_id'=>'nullable|exists:floor_plans,id','survey_area_id'=>'nullable|exists:survey_areas,id','name'=>'required|string|max:180','purpose'=>'required|string|max:80','target_ssid'=>'nullable|string|max:100','minimum_points'=>'required|integer|between:3,500','notes'=>'nullable|string|max:5000']);$floor=Floor::findOrFail($d['floor_id']);if(!empty($d['floor_plan_id'])){abort_unless($floor->floorPlans()->whereKey($d['floor_plan_id'])->exists(),422);}else{$d['floor_plan_id']=null;}$d['created_by']=$r->user()->id;return response()->json(Survey::create($d),201);}
 public function show(Request $r,Survey $survey):View{abort_unless($r->user()->can('dashboard.view'),403);$survey->load(['floor.building.campus.institution','floorPlan','surveyArea','points.measurements','runs']);return view('surveys.workspace',compact('survey'));}
 public function start(Request $r,Survey $survey,SurveyLifecycleService $service):JsonResponse{abort_unless($r->user()->can('surveys.manage'),403);$d=$r->validate(['device_name'=>'nullable|string|max:120','device_platform'=>'nullable|string|max:120','collector_version'=>'nullable|string|max:60','environment'=>'nullable|array']);return response()->json($service->start($survey,$r->user()->id,$d),201);}
 public function validateSurvey(Request $r,Survey $survey,SurveyLifecycleService $service):JsonResponse{abort_unless($r->user()->can('surveys.manage'),403);return response()->json($service->validateForCompletion($survey));}
 public function complete(Request $r,Survey $survey,SurveyLifecycleService $service):JsonResponse{abort_unless($r->user()->can('surveys.manage'),403);return response()->json($service->complete($survey));}
}
