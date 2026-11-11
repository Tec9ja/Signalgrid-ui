<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Explainable Insights | SignalGrid</title>
@vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
<header class="domain-header">
<div>
<span class="eyebrow">EVIDENCE-BASED DECISIONS</span>
<h1>Explainable insights</h1>
<p>Every conclusion shows evidence, thresholds, confidence, recommendation and limitations.</p>
</div>
<a class="secondary" href="{{ route('dashboard') }}">Dashboard</a>
</header>
@can('insights.review')
<section class="panel">
<h2>Generate insights</h2>
<form class="ops-form wide" id="generateInsights">
<label>Survey<select id="insightSurvey" required><option value="">Select survey</option>@foreach($surveys as $survey)<option value="{{ $survey->id }}">{{ $survey->name }} · {{ $survey->floor->building->name }}</option>@endforeach</select></label>
<label>From<input id="insightFrom" type="date"></label>
<label>To<input id="insightTo" type="date"></label>
<button class="primary" type="submit">Generate</button>
<p id="insightMessage" data-form-message></p>
</form>
</section>
@endcan
<p id="insightStatus"></p>
<section class="insight-real-grid">
@forelse($insights as $insight)
<article class="panel insight-real-card" id="insight-{{ $insight->id }}">
<header>
<span class="sev {{ $insight->severity==='high'?'high':($insight->severity==='medium'?'med':'low') }}">{{ ucfirst($insight->severity) }}</span>
<div>
<small>{{ $insight->rule->code }} · {{ strtoupper($insight->confidence) }} CONFIDENCE</small>
<h2>{{ $insight->title }}</h2>
<p>{{ optional($insight->floor->building)->name }} · {{ optional($insight->floor)->name }}</p>
</div>
<em>{{ ucfirst($insight->status) }}</em>
</header>
<div class="insight-evidence">
@foreach($insight->evidence ?? [] as $key => $value)
<div>
<small>{{ str_replace('_',' ',ucfirst($key)) }}</small>
<b>{{ is_scalar($value) || $value === null ? $value : json_encode($value) }}</b>
</div>
@endforeach
</div>
<section><h4>Identified problem</h4><p>{{ $insight->classification }}</p></section>
<section><h4>Recommended action</h4><p>{{ $insight->recommendation }}</p></section>
@if($insight->limitations)
<section class="limitation"><strong>Limitations</strong><p>{{ implode(' ', array_filter($insight->limitations)) }}</p></section>
@endif
<footer>
<span>{{ $insight->period_start->format('d M') }} to {{ $insight->period_end->format('d M Y') }}</span>
<span>{{ $insight->actions->count() }} action item(s)</span>
</footer>
@can('insights.review')
<div class="ops-actions">
<label>Status
<select data-review="{{ route('insights.review',$insight) }}" data-message="#insightStatus">
@if(!in_array($insight->status, ['reviewed','investigating','resolved','dismissed'], true))
<option value="{{ $insight->status }}" selected>{{ ucfirst($insight->status) }}</option>
@endif
@foreach(['reviewed','investigating','resolved','dismissed'] as $status)
<option value="{{ $status }}" @selected($insight->status===$status)>{{ ucfirst($status) }}</option>
@endforeach
</select>
</label>
<form class="ops-form" data-json="{{ route('insights.actions.store',$insight) }}">
<label>Action title<input name="title" required maxlength="180"></label>
<label>Assign<select name="assigned_to"><option value="">Unassigned</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></label>
<label>Due<input name="due_date" type="date"></label>
<button class="secondary" type="submit">Create action item</button>
<p data-form-message></p>
</form>
</div>
@endcan
</article>
@empty
<div class="panel domain-empty"><h2>No insights generated</h2><p>Generate insights from a survey after real measurements are available.</p></div>
@endforelse
</section>
{{ $insights->links() }}
</main>
@can('insights.review')
<script>
document.querySelector('#generateInsights')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const message = document.querySelector('#insightMessage');
    const id = document.querySelector('#insightSurvey').value;
    if (!id) { message.textContent = 'Select a survey.'; return; }
    const body = {};
    const from = document.querySelector('#insightFrom').value;
    const to = document.querySelector('#insightTo').value;
    if (from) body.from = from;
    if (to) body.to = to;
    message.textContent = 'Generating insights…';
    const response = await fetch(`/api/surveys/${id}/insights/generate`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify(body),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) { message.textContent = data.message || 'Insight generation failed.'; return; }
    window.location.reload();
});
</script>
@endcan
</body>
</html>
