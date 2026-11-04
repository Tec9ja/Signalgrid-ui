<?php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FloorPlanController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationManagementController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
Route::middleware('guest')->group(function(){Route::get('/login',[AuthenticatedSessionController::class,'create'])->name('login');Route::post('/login',[AuthenticatedSessionController::class,'store'])->middleware('throttle:5,1')->name('login.store');});
Route::post('/logout',[AuthenticatedSessionController::class,'destroy'])->middleware('auth')->name('logout');
Route::redirect('/','/dashboard');
Route::get('/dashboard',[DashboardController::class,'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function(){
 Route::get('/locations',[LocationController::class,'index'])->name('locations.index');
 Route::get('/api/location-tree',[LocationController::class,'tree'])->name('locations.tree');
 Route::post('/api/institutions/{institution}/campuses',[LocationManagementController::class,'campus']);
 Route::post('/api/campuses/{campus}/buildings',[LocationManagementController::class,'building']);
 Route::post('/api/buildings/{building}/floors',[LocationManagementController::class,'floor']);
 Route::post('/api/floors/{floor}/survey-areas',[LocationManagementController::class,'area']);
 Route::post('/api/floors/{floor}/floor-plans',[FloorPlanController::class,'store']);
 Route::delete('/api/floor-plans/{floorPlan}',[FloorPlanController::class,'destroy']);
});

Route::get('/health',function(){try{DB::select('select 1');return response()->json(['status'=>'ok','application'=>config('app.name'),'environment'=>app()->environment(),'database'=>'connected','timestamp'=>now()->toIso8601String()]);}catch(\Throwable $e){report($e);return response()->json(['status'=>'error','database'=>'unavailable'],503);}})->name('health');
