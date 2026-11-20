<?php
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ValidationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\ProjectAlignmentController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\FeedbackAdminController;
use App\Http\Controllers\PublicFeedbackController;
use App\Http\Controllers\AccessPointController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\MeasurementTemplateController;
use App\Http\Controllers\MeasurementImportController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\MeasurementPointController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FloorPlanController;
use App\Http\Controllers\FloorPlanWorkspaceController;
use App\Http\Controllers\CampusSetupController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationManagementController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
Route::middleware('guest')->group(function(){Route::get('/login',[AuthenticatedSessionController::class,'create'])->name('login');Route::post('/login',[AuthenticatedSessionController::class,'store'])->middleware('throttle:5,1')->name('login.store');});
Route::post('/logout',[AuthenticatedSessionController::class,'destroy'])->middleware('auth')->name('logout');
Route::redirect('/','/dashboard');
Route::get('/class-guide.pdf', function () {
    return response()->file(base_path('docs/SignalGrid-admin-guide.pdf'), [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="SignalGrid-admin-guide.pdf"',
    ]);
})->name('guides.admin');
Route::get('/class-walkthrough.mp4', function () {
    return response()->file(base_path('docs/SignalGrid-walkthrough.mp4'), [
        'Content-Type' => 'video/mp4',
        'Content-Disposition' => 'inline; filename="SignalGrid-walkthrough.mp4"',
    ]);
})->name('guides.walkthrough');
Route::get('/dashboard',[DashboardController::class,'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function(){
 Route::patch('/api/floor-plans/{floorPlan}/calibrate',[FloorPlanController::class,'calibrate'])->name('floor-plans.calibrate');
 Route::patch('/api/floor-plans/{floorPlan}/boundary',[FloorPlanController::class,'boundary'])->name('floor-plans.boundary');
 Route::patch('/api/floor-plans/{floorPlan}/activate',[FloorPlanController::class,'activate'])->name('floor-plans.activate');
 Route::delete('/api/floor-plans/{floorPlan}',[FloorPlanController::class,'destroy'])->name('floor-plans.destroy');

 Route::get('/locations',[LocationController::class,'index'])->name('locations.index');
 Route::get('/campus-setup',[CampusSetupController::class,'show'])->name('campus-setup.show');
 Route::post('/campus-setup',[CampusSetupController::class,'store'])->name('campus-setup.store');
 Route::get('/api/location-tree',[LocationController::class,'tree'])->name('locations.tree');
 Route::post('/api/institutions/{institution}/campuses',[LocationManagementController::class,'campus']);
 Route::post('/api/campuses/{campus}/buildings',[LocationManagementController::class,'building']);
 Route::post('/api/buildings/{building}/floors',[LocationManagementController::class,'floor']);
 Route::post('/api/floors/{floor}/survey-areas',[LocationManagementController::class,'area']);
 Route::post('/api/floors/{floor}/floor-plans',[FloorPlanController::class,'store'])->name('floor-plans.store');
 Route::get('/floors/{floor}/floor-plan',[FloorPlanWorkspaceController::class,'show'])->name('floor-plans.workspace');
 Route::get('/floor-plans/{floorPlan}/file',[FloorPlanController::class,'file'])->name('floor-plans.file');
 Route::patch('/api/floor-plans/{floorPlan}/calibrate',[FloorPlanController::class,'calibrate']);
 Route::patch('/api/floor-plans/{floorPlan}/boundary',[FloorPlanController::class,'boundary']);
 Route::patch('/api/floor-plans/{floorPlan}/activate',[FloorPlanController::class,'activate']);
 Route::delete('/api/floor-plans/{floorPlan}',[FloorPlanController::class,'destroy']);
});

Route::get('/health',function(){try{DB::select('select 1');return response()->json(['status'=>'ok','application'=>config('app.name'),'environment'=>app()->environment(),'database'=>'connected','timestamp'=>now()->toIso8601String()]);}catch(\Throwable $e){report($e);return response()->json(['status'=>'error','database'=>'unavailable'],503);}})->name('health');

Route::middleware('auth')->group(function(){
 Route::get('/surveys',[SurveyController::class,'index'])->name('surveys.index');
 Route::post('/api/surveys',[SurveyController::class,'store'])->name('surveys.store');
 Route::get('/surveys/{survey}',[SurveyController::class,'show'])->name('surveys.show');
 Route::post('/api/surveys/{survey}/start',[SurveyController::class,'start'])->name('surveys.start');
 Route::get('/api/surveys/{survey}/validate',[SurveyController::class,'validateSurvey'])->name('surveys.validate');
 Route::post('/api/surveys/{survey}/complete',[SurveyController::class,'complete'])->name('surveys.complete');
 Route::post('/api/surveys/{survey}/points',[MeasurementPointController::class,'store'])->name('survey-points.store');
 Route::patch('/api/measurement-points/{measurementPoint}',[MeasurementPointController::class,'update'])->name('survey-points.update');
 Route::delete('/api/measurement-points/{measurementPoint}',[MeasurementPointController::class,'destroy'])->name('survey-points.destroy');
});

Route::middleware('auth')->group(function(){
 Route::post('/api/measurement-points/{measurementPoint}/measurements',[MeasurementController::class,'store'])->name('measurements.store');
 Route::post('/api/surveys/{survey}/measurement-imports',[MeasurementImportController::class,'store'])->name('measurement-imports.store');
 Route::get('/api/measurement-imports/{measurementImport}/errors',[MeasurementImportController::class,'errors'])->name('measurement-imports.errors');
 Route::get('/measurement-import-template.csv',MeasurementTemplateController::class)->name('measurement-imports.template');
});

Route::middleware('auth')->group(function(){
 Route::get('/surveys/{survey}/heatmaps',[HeatmapController::class,'index'])->name('heatmaps.index');
 Route::post('/api/surveys/{survey}/heatmaps',[HeatmapController::class,'store'])->name('heatmaps.store');
 Route::get('/api/heatmaps/{heatmapRun}',[HeatmapController::class,'show'])->name('heatmaps.show');
});

Route::get('/api/analytics/dashboard', [AnalyticsController::class, 'dashboard'])
    ->middleware('auth')
    ->name('analytics.dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/measurements', [MeasurementController::class, 'index'])->name('measurements.index');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
});

Route::middleware('auth')->group(function(){
 Route::get('/access-points',[AccessPointController::class,'index'])->name('access-points.index');
 Route::post('/api/access-points',[AccessPointController::class,'store'])->name('access-points.store');
 Route::post('/api/access-points/{accessPoint}/samples',[AccessPointController::class,'sample'])->name('access-points.samples.store');
 Route::get('/api/access-points/{accessPoint}/analysis',[AccessPointController::class,'analysis'])->name('access-points.analysis');
});

Route::middleware('throttle:20,1')->group(function(){
 Route::get('/feedback',[PublicFeedbackController::class,'create'])->name('feedback.public');
 Route::post('/feedback',[PublicFeedbackController::class,'store'])->name('feedback.public.store');
 Route::get('/feedback/thank-you/{publicId}',[PublicFeedbackController::class,'thanks'])->name('feedback.thank-you');
});
Route::middleware('auth')->group(function(){
 Route::get('/admin/feedback',[FeedbackAdminController::class,'index'])->name('feedback.index');
 Route::post('/api/feedback/{feedbackResponse}/correlate',[FeedbackAdminController::class,'correlate'])->name('feedback.correlate');
});

Route::middleware('auth')->group(function(){
 Route::get('/insights',[InsightController::class,'index'])->name('insights.index');
 Route::post('/api/surveys/{survey}/insights/generate',[InsightController::class,'generate'])->name('insights.generate');
 Route::patch('/api/insights/{insight}/review',[InsightController::class,'review'])->name('insights.review');
 Route::post('/api/insights/{insight}/actions',[InsightController::class,'action'])->name('insights.actions.store');
});

Route::middleware('auth')->group(function(){
 Route::get('/project-alignment',[ProjectAlignmentController::class,'index'])->name('project.alignment');
 Route::get('/api/project-alignment',[ProjectAlignmentController::class,'show'])->name('project.alignment.api');
 Route::get('/comparisons',[ComparisonController::class,'index'])->name('comparisons.index');
 Route::post('/api/comparisons',[ComparisonController::class,'store'])->name('comparisons.store');
 Route::get('/api/comparisons/{comparisonRun}',[ComparisonController::class,'show'])->name('comparisons.show');
});

Route::middleware('auth')->group(function(){
 Route::get('/reports',[ReportController::class,'index'])->name('reports.index');
 Route::post('/api/reports',[ReportController::class,'store'])->name('reports.store');
 Route::get('/reports/{report}/preview',[ReportController::class,'preview'])->name('reports.preview');
 Route::get('/reports/{report}/pdf',[ReportController::class,'pdf'])->name('reports.pdf');
 Route::get('/reports/{report}/csv',[ReportController::class,'csv'])->name('reports.csv');
});

Route::middleware(['auth','audit.mutations'])->group(function(){
 Route::get('/admin',[AdminController::class,'index'])->name('admin.index');
 Route::patch('/api/admin/users/{user}',[AdminController::class,'updateUser'])->name('admin.users.update');
 Route::get('/admin/audit-logs',[AdminController::class,'auditLogs'])->name('admin.audit');
 Route::get('/api/admin/health',[AdminController::class,'health'])->name('admin.health');
 Route::get('/api/notifications',[AdminController::class,'notifications'])->name('notifications.index');
 Route::patch('/api/notifications/{notificationId}/read',[AdminController::class,'markNotificationRead'])->name('notifications.read');
 Route::put('/api/notification-preferences',[AdminController::class,'preference'])->name('notifications.preferences');
});

Route::middleware('auth')->group(function(){
 Route::get('/validation',[ValidationController::class,'index'])->name('validation.index');
 Route::post('/api/surveys/{survey}/validation',[ValidationController::class,'scientific'])->name('validation.scientific');
 Route::post('/api/validation/acceptance',[ValidationController::class,'acceptance'])->name('validation.acceptance');
 Route::post('/api/research-observations',[ValidationController::class,'observation'])->name('validation.observations.store');
});

Route::middleware('guest')->group(function(){
 Route::get('/register',[RegisteredUserController::class,'create'])->name('register');
 Route::post('/register',[RegisteredUserController::class,'store'])->middleware('throttle:5,1')->name('register.store');
});
Route::middleware('auth')->group(function(){
 Route::get('/onboarding',[OnboardingController::class,'show'])->name('onboarding.show');
 Route::post('/onboarding',[OnboardingController::class,'store'])->name('onboarding.store');
 Route::post('/onboarding/skip',[OnboardingController::class,'skip'])->name('onboarding.skip');
});
