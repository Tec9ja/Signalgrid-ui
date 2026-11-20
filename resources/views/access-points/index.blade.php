<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AP Load Analysis | SignalGrid</title>
@vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
<header class="domain-header">
<div>
<span class="eyebrow">CAPACITY & CONGESTION</span>
<h1>Access-point load analysis</h1>
<p>{{ $from->format('d M H:i') }} to {{ $to->format('d M H:i') }}</p>
</div>
<a class="secondary" href="{{ route('dashboard') }}">Dashboard</a>
</header>
@can('locations.manage')
<section class="panel">
<h2>Add access point</h2>
<p>Choose the floor. A floor plan is not required.</p>
<form class="ops-form wide" data-json="{{ route('access-points.store') }}">
<label>Floor<select name="floor_id" required><option value="">Select floor</option>@foreach($floors as $floor)<option value="{{ $floor->id }}">{{ $floor->building->name }} · {{ $floor->name }}</option>@endforeach</select></label>
<label>Name<input name="name" required maxlength="150"></label>
<label>Identifier<input name="identifier" required maxlength="120" placeholder="AP-LIB-04"></label>
<label>Vendor<input name="vendor" maxlength="100"></label>
<label>Model<input name="model" maxlength="100"></label>
<label>Client limit<input name="configured_client_limit" type="number" min="1" max="5000"></label>
<button class="primary" type="submit">Save access point</button>
<p data-form-message></p>
</form>
</section>
@endcan
<section class="panel">
<div class="ap-list-head"><b>Access point</b><b>Location</b><b>Clients</b><b>Utilisation</b><b>Assessment</b></div>
@forelse($aps as $ap)
<article class="ap-list-row">
<span><b>{{ $ap->name }}</b><small>{{ $ap->identifier }} · {{ $ap->vendor ?? 'Vendor unavailable' }}</small></span>
<span><a href="{{ route('locations.index') }}">{{ $ap->floor->building->name }} · {{ $ap->floor->name }}</a></span>
<strong>{{ $ap->latestLoadSample?->connected_clients ?? '—' }}</strong>
<strong>{{ $ap->latestLoadSample?->channel_utilisation_percent === null ? '—' : number_format((float) $ap->latestLoadSample->channel_utilisation_percent, 1).'%' }}</strong>
<div>
<em class="survey-status {{ ['possible_congestion'=>'is-congestion','no_congestion_detected'=>'is-clear','no_congestion_evidence'=>'is-clear','insufficient_data'=>'is-unknown','insufficient_evidence'=>'is-unknown'][$ap->analysis['classification']] ?? 'is-unknown' }}">{{ ['possible_congestion'=>'Possible congestion','no_congestion_detected'=>'No congestion detected','no_congestion_evidence'=>'No congestion detected','insufficient_data'=>'Insufficient data','insufficient_evidence'=>'Insufficient data'][$ap->analysis['classification']] ?? str_replace('_',' ',ucfirst($ap->analysis['classification'])) }}</em>
<small>{{ implode(' ', $ap->analysis['limitations']) }}</small>
</div>
</article>
@can('measurements.import')
<form class="ops-form compact" data-json="{{ route('access-points.samples.store',$ap) }}">
<label>Clients<input name="connected_clients" type="number" min="0"></label>
<label>Utilisation %<input name="channel_utilisation_percent" type="number" min="0" max="100" step="0.1"></label>
<label>Latency ms<input name="latency_ms" type="number" min="0" step="0.1"></label>
<input type="hidden" name="source_type" value="manual">
<label>Sampled at<input name="sampled_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}"></label>
<button class="secondary" type="submit">Record sample for {{ $ap->name }}</button>
<p data-form-message></p>
</form>
@endcan
@empty
<div class="domain-empty"><h2>No access points configured</h2><p>Add an access point, then record a manual sample or import controller telemetry.</p></div>
@endforelse
</section>
</main>
</body>
</html>
