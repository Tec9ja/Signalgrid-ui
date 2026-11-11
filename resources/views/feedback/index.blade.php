<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>User Feedback | SignalGrid</title>
@vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
<header class="domain-header">
<div>
<span class="eyebrow">USER EXPERIENCE</span>
<h1>Feedback and evidence correlation</h1>
<p>{{ $stats['count'] }} responses · Average {{ $stats['average_satisfaction'] ?: '—' }}/5</p>
</div>
<div class="header-links">
<a class="primary" href="{{ route('feedback.public') }}" target="_blank" rel="noopener">Share feedback form</a>
<a class="secondary" href="{{ route('dashboard') }}">Dashboard</a>
</div>
</header>
<p id="feedbackMessage"></p>
<section class="feedback-stat-grid">
@foreach($stats['common_problems'] as $problem)
<article class="panel"><small>{{ str_replace('_',' ',ucfirst($problem->problem_type)) }}</small><b>{{ $problem->total }}</b></article>
@endforeach
</section>
<section class="panel">
<div class="feedback-admin-head"><b>Location</b><b>Problem</b><b>Satisfaction</b><b>Evidence</b><b>Time</b><b></b></div>
@forelse($feedback as $item)
<div class="feedback-admin-row">
<span><b>{{ $item->building->name }}</b><small>{{ optional($item->floor)->name }} · {{ optional($item->surveyArea)->name }}</small></span>
<span>{{ str_replace('_',' ',$item->problem_type) }}</span>
<strong>{{ $item->overall_satisfaction }}/5</strong>
<em>{{ $item->correlations->count() ? $item->correlations->pluck('strength')->sort()->first().' correlation' : 'insufficient data' }}</em>
<small>{{ $item->experienced_at->format('d M H:i') }}</small>
@can('insights.review')
<button class="secondary" type="button" data-correlate="{{ route('feedback.correlate',$item) }}">Correlate</button>
@else
<span></span>
@endcan
</div>
@empty
<div class="domain-empty"><h2>No feedback submitted</h2><p>Share the public form so campus users can report what they experienced.</p></div>
@endforelse
</section>
{{ $feedback->links() }}
</main>
</body>
</html>
