<?php
namespace App\Http\Controllers;
use App\Models\Floor;use Illuminate\Http\Request;use Illuminate\View\View;
class FloorPlanWorkspaceController extends Controller {
 public function show(Request $r,Floor $floor):View{abort_unless($r->user()->can('dashboard.view'),403);$floor->load(['building.campus.institution','surveyAreas','floorPlans'=>fn($q)=>$q->latest('version')]);return view('floor-plans.workspace',compact('floor'));}
}
