<?php
namespace App\Http\Controllers;
use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class LocationController extends Controller {
 public function index(Request $r):View{abort_unless($r->user()->can('dashboard.view'),403);$institutions=Institution::with(['campuses.buildings.floors.surveyAreas','campuses.buildings.floors.activeFloorPlan'])->orderBy('name')->get();return view('locations.index',compact('institutions'));}
 public function tree(Request $r):JsonResponse{abort_unless($r->user()->can('dashboard.view'),403);return response()->json(Institution::with(['campuses.buildings.floors.surveyAreas'])->get());}
}
