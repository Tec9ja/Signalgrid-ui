<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Locations | SignalGrid</title>
@vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
<header class="domain-header">
<div>
<span class="eyebrow">CAMPUS STRUCTURE</span>
<h1>Location hierarchy</h1>
<p>Campus, building, floor and survey area. Open a floor to calibrate its plan.</p>
</div>
<div class="header-links">
@can('locations.manage')<a class="primary" href="{{ route('campus-setup.show') }}">Campus setup</a>@endcan
<a class="secondary" href="{{ route('dashboard') }}">Back to dashboard</a>
</div>
</header>
@if(session('status'))
<p class="setup-callout" role="status">{{ session('status') }}</p>
@endif
@forelse($institutions as $institution)
<section class="panel domain-card campus-record">
<header class="campus-record-head">
<div>
<span class="eyebrow">INSTITUTION</span>
<h2>{{ $institution->name }}</h2>
<p>{{ ucfirst(str_replace('_', ' ', $institution->type)) }} · {{ $institution->timezone }}</p>
@if($institution->motto)<p class="campus-motto">{{ $institution->motto }}</p>@endif
@if($institution->website)<p><a href="{{ $institution->website }}" target="_blank" rel="noopener noreferrer">{{ $institution->website }}</a></p>@endif
</div>
</header>
@foreach($institution->campuses as $campus)
@php
    $latText = $campus->latitude !== null ? rtrim(rtrim(number_format((float) $campus->latitude, 6, '.', ''), '0'), '.') : null;
    $lngText = $campus->longitude !== null ? rtrim(rtrim(number_format((float) $campus->longitude, 6, '.', ''), '0'), '.') : null;
@endphp
<article class="campus-block">
<header class="campus-block-head">
<div>
<h3>{{ $campus->name }}</h3>
<p>{{ $campus->code }} · {{ $campus->buildings->count() }} {{ $campus->buildings->count() === 1 ? 'building' : 'buildings' }}</p>
</div>
</header>
<div class="campus-overview">
<dl class="campus-facts">
<div>
<dt>Address</dt>
<dd>{{ $campus->address ?: 'Not set yet' }}</dd>
</div>
<div>
<dt>Place</dt>
<dd>{{ collect([$campus->locality, $campus->country])->filter()->join(', ') ?: 'Not set yet' }}</dd>
</div>
<div>
<dt>Coordinates</dt>
<dd>
@if($latText && $lngText)
{{ $latText }}, {{ $lngText }}
@else
Not set yet
@endif
</dd>
</div>
<div>
<dt>Source</dt>
<dd>
@if($campus->reference_source)
{{ $campus->reference_source === 'public_record' ? 'Public record' : 'Entered by an administrator' }}@if($campus->setup_completed_at) · {{ $campus->setup_completed_at->timezone(config('app.timezone'))->format('d M Y') }}@endif
@else
Not recorded
@endif
</dd>
</div>
</dl>
@include('partials.campus-map', [
    'tile' => \App\Support\MapTile::from($campus->latitude, $campus->longitude),
    'mapsUrl' => $campus->googleMapsUrl(),
    'title' => $campus->name,
    'empty' => 'Add a coordinate in Campus setup to show the map.',
])
</div>
@if($campus->setup_note)
<details class="campus-note">
<summary>How this record was gathered</summary>
<p>{{ $campus->setup_note }}</p>
</details>
@endif
@if($campus->buildings->isNotEmpty() && $campus->buildings->every(fn ($building) => $building->floors->isEmpty()))
<p class="campus-floor-note">No floors are recorded yet. Add a floor on a building after a site visit. Public listings do not include interior floor plans.</p>
@endif
<div class="domain-grid">
@foreach($campus->buildings->sortBy(fn ($building) => sprintf('%05d-%s', $building->display_order, $building->name)) as $building)
<article class="building-card">
<div class="building-card-top">
<h3>{{ $building->name }}</h3>
<span class="building-code">{{ $building->code }}</span>
</div>
@if($building->description)<p>{{ $building->description }}</p>@endif
@if($building->floors->isEmpty() && ! $campus->buildings->every(fn ($item) => $item->floors->isEmpty()))
<p class="building-empty">No floors recorded.</p>
@endif
@foreach($building->floors as $floor)
<div class="floor-row">
<div>
<b>{{ $floor->name }}</b>
<small>Level {{ $floor->level_number }} · {{ $floor->surveyAreas->count() }} survey areas · {{ $floor->activeFloorPlan ? 'Floor plan ready' : 'No floor plan' }}</small>
@if($floor->surveyAreas->isNotEmpty())
<small>{{ $floor->surveyAreas->pluck('name')->join(', ') }}</small>
@endif
</div>
<div class="row-actions">
<a href="{{ route('floor-plans.workspace', $floor) }}">Open floor plan</a>
</div>
</div>
@can('locations.manage')
<details class="quiet-form">
<summary>Add area on {{ $floor->name }}</summary>
<form class="ops-form compact" data-json="{{ url('/api/floors/'.$floor->id.'/survey-areas') }}">
<label>Area name<input name="name" required maxlength="150"></label>
<label>Code<input name="code" required maxlength="30"></label>
<label>Type<input name="area_type" required maxlength="40" value="general"></label>
<button class="secondary" type="submit">Add area</button>
<p data-form-message></p>
</form>
</details>
@endcan
@endforeach
@can('locations.manage')
<details class="quiet-form">
<summary>Add floor</summary>
<form class="ops-form" data-json="{{ url('/api/buildings/'.$building->id.'/floors') }}">
<label>Floor name<input name="name" required maxlength="100"></label>
<label>Code<input name="code" required maxlength="30"></label>
<label>Level<input name="level_number" type="number" required min="-20" max="200" value="0"></label>
<button class="secondary" type="submit">Add floor</button>
<p data-form-message></p>
</form>
</details>
@endcan
</article>
@endforeach
</div>
@can('locations.manage')
<details class="quiet-form">
<summary>Add building to {{ $campus->name }}</summary>
<form class="ops-form" data-json="{{ url('/api/campuses/'.$campus->id.'/buildings') }}">
<label>Name<input name="name" required maxlength="150"></label>
<label>Code<input name="code" required maxlength="30"></label>
<button class="secondary" type="submit">Save building</button>
<p data-form-message></p>
</form>
</details>
@endcan
</article>
@endforeach
@can('locations.manage')
<details class="quiet-form">
<summary>Add another campus</summary>
<form class="ops-form" data-json="{{ url('/api/institutions/'.$institution->id.'/campuses') }}">
<label>Name<input name="name" required maxlength="150"></label>
<label>Code<input name="code" required maxlength="30"></label>
<label>Address<input name="address" maxlength="1000"></label>
<button class="primary" type="submit">Save campus</button>
<p data-form-message></p>
</form>
</details>
@endcan
</section>
@empty
<section class="panel domain-empty">
<h2>No locations yet</h2>
<p>Open Campus setup to load the published AAUA record, or enter another campus.</p>
@can('locations.manage')<a class="primary" href="{{ route('campus-setup.show') }}">Open campus setup</a>@endcan
</section>
@endforelse
</main>
</body>
</html>
