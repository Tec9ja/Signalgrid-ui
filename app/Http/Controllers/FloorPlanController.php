<?php
namespace App\Http\Controllers;
use App\Models\Floor;use App\Models\FloorPlan;use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Storage;
class FloorPlanController extends Controller {
 public function store(Request $r,Floor $floor):JsonResponse{abort_unless($r->user()->can('locations.manage'),403);$d=$r->validate(['floor_plan'=>'required|file|mimes:png,jpg,jpeg,pdf|max:20480']);$f=$d['floor_plan'];$path=$f->store("floor-plans/{$floor->id}",'local');$plan=DB::transaction(function()use($r,$floor,$f,$path){$floor->floorPlans()->where('is_active',true)->update(['is_active'=>false]);return $floor->floorPlans()->create(['uploaded_by'=>$r->user()->id,'original_name'=>$f->getClientOriginalName(),'file_path'=>$path,'mime_type'=>$f->getMimeType(),'file_size'=>$f->getSize(),'version'=>($floor->floorPlans()->max('version')??0)+1,'is_active'=>true]);});return response()->json($plan,201);}
 public function destroy(Request $r,FloorPlan $floorPlan):JsonResponse{abort_unless($r->user()->can('locations.manage'),403);Storage::disk('local')->delete($floorPlan->file_path);$floorPlan->delete();return response()->json(status:204);}
}
