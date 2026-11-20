<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Surveys | SignalGrid</title>
@vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
<header class="domain-header">
<div>
<span class="eyebrow">RF SURVEYS</span>
<h1>Survey management</h1>
<p>Plan a survey, open its workspace, then generate a coverage heat map.</p>
</div>
<a class="secondary" href="{{ route('dashboard') }}">Dashboard</a>
</header>
@can('surveys.manage')
<section class="panel" id="import-measurements">
<h2>New survey</h2>
<p>A floor plan is optional. Readings are recorded in the survey workspace. Add a floor plan only when you want a heat map.</p>
<form class="ops-form wide" data-json="{{ route('surveys.store') }}">
<label>Floor<select id="surveyFloor" name="floor_id" required><option value="">Select floor</option>@foreach($floors as $floor)<option value="{{ $floor->id }}">{{ $floor->building->name }} · {{ $floor->name }}</option>@endforeach</select></label>
<label>Floor plan<select id="surveyPlan" name="floor_plan_id"><option value="">No floor plan</option></select></label>
<label>Survey area<select id="surveyArea" name="survey_area_id"><option value="">Whole floor</option></select></label>
<label>Name<input name="name" required maxlength="180" placeholder="Library ground baseline"></label>
<label>Purpose<input name="purpose" required maxlength="80" value="baseline"></label>
<label>Target SSID<input name="target_ssid" maxlength="100"></label>
<label>Minimum points<input name="minimum_points" type="number" min="3" max="500" value="12" required></label>
<button class="primary" type="submit">Create survey</button>
<p data-form-message></p>
</form>
</section>
<script>window.surveyCreateFloors=@json($surveyCreateFloors);</script>
@else
<section class="panel" id="import-measurements">
<p>Your account can review surveys. Creating a survey or importing measurements requires a surveyor or administrator.</p>
<a class="secondary" href="{{ route('measurement-imports.template') }}">Download measurement template</a>
</section>
@endcan
<section class="panel" id="coverage">
<div class="survey-list-head"><b>Name</b><b>Location</b><b>Status</b><b>Points</b><b>Actions</b></div>
@forelse($surveys as $survey)
<div class="survey-list-row">
<span><b>{{ $survey->name }}</b><small>{{ $survey->purpose }}</small></span>
<span>{{ $survey->floor->building->name }} · {{ $survey->floor->name }}</span>
<em class="survey-status {{ $survey->status }}">{{ ucfirst($survey->status) }}</em>
<strong>{{ $survey->points_count }}</strong>
<span class="row-actions">
<a href="{{ route('surveys.show',$survey) }}">Open</a>
<a href="{{ route('heatmaps.index',$survey) }}">Heat map</a>
<a href="{{ route('floor-plans.workspace',$survey->floor) }}">{{ $survey->floorPlan ? 'Floor plan' : 'Add floor plan' }}</a>
</span>
</div>
@empty
<div class="domain-empty"><h2>No surveys yet</h2><p>Create a survey for any floor. A floor plan is optional until you want a heat map.</p></div>
@endforelse
</section>
{{ $surveys->links() }}
</main>
</body>
</html>
