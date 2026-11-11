<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Measurements | SignalGrid</title>
@vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
<header class="domain-header">
<div>
<span class="eyebrow">DATA COLLECTION</span>
<h1>Measurements</h1>
<p>Recorded signal, speed and latency values, with their source.</p>
</div>
<div class="header-links">
<a class="secondary" href="{{ route('surveys.index') }}">Survey studio</a>
<a class="secondary" href="{{ route('measurement-imports.template') }}">CSV template</a>
</div>
</header>
<section class="panel">
<div class="measure-head"><b>When</b><b>Location</b><b>RSSI</b><b>Download</b><b>Latency</b><b>Source</b></div>
@forelse($measurements as $measurement)
<a class="measure-row" href="{{ route('surveys.show',$measurement->point->survey) }}">
<span>{{ optional($measurement->captured_at)->format('d M Y H:i') }}</span>
<span>{{ $measurement->point->survey->floor->building->name }} · {{ $measurement->point->survey->name }}</span>
<strong>{{ $measurement->rssi_dbm !== null ? $measurement->rssi_dbm.' dBm' : '—' }}</strong>
<strong>{{ $measurement->download_mbps !== null ? $measurement->download_mbps.' Mbps' : '—' }}</strong>
<strong>{{ $measurement->latency_ms !== null ? $measurement->latency_ms.' ms' : '—' }}</strong>
<em>{{ ucfirst($measurement->source_type) }}</em>
</a>
@empty
<div class="domain-empty">
<h2>No measurements yet</h2>
<p>Open a survey, place a point, and save a reading or import a CSV.</p>
<a class="primary" href="{{ route('surveys.index') }}">Go to surveys</a>
</div>
@endforelse
</section>
{{ $measurements->links() }}
</main>
</body>
</html>
