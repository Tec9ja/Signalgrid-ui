<?php
namespace App\Http\Controllers;
use App\Models\{Institution,Campus,Building,Floor,SurveyArea};
use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;use Illuminate\Support\Str;
class LocationManagementController extends Controller {
 private function permit(Request $r):void{abort_unless($r->user()->can('locations.manage'),403);}
 public function campus(Request $r,Institution $institution):JsonResponse{$this->permit($r);$d=$r->validate(['name'=>'required|string|max:150','code'=>'required|string|max:30','address'=>'nullable|string|max:1000']);return response()->json($institution->campuses()->create($d),201);}
 public function building(Request $r,Campus $campus):JsonResponse{$this->permit($r);$d=$r->validate(['name'=>'required|string|max:150','code'=>'required|string|max:30','description'=>'nullable|string|max:2000']);return response()->json($campus->buildings()->create($d),201);}
 public function floor(Request $r,Building $building):JsonResponse{$this->permit($r);$d=$r->validate(['name'=>'required|string|max:100','code'=>'required|string|max:30','level_number'=>'required|integer|between:-20,200','area_sqm'=>'nullable|numeric|min:0']);return response()->json($building->floors()->create($d),201);}
 public function area(Request $r,Floor $floor):JsonResponse{$this->permit($r);$d=$r->validate(['name'=>'required|string|max:150','code'=>'required|string|max:30','area_type'=>'required|string|max:40','area_sqm'=>'nullable|numeric|min:0','boundary_points'=>'nullable|array']);return response()->json($floor->surveyAreas()->create($d),201);}
}
