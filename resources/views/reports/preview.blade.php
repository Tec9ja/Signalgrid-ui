<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $report->title }} | SignalGrid</title>
    @vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
    <header class="domain-header">
        <div>
            <span class="eyebrow">REPORT PREVIEW</span>
            <h1>{{ $report->title }}</h1>
            <p>{{ data_get($report->snapshot, 'project.institution') }} · {{ $report->period_start->format('d M Y') }} to {{ $report->period_end->format('d M Y') }} · Method v{{ $report->methodology_version }}</p>
        </div>
        <div class="header-links">
            <a class="secondary" href="{{ route('reports.pdf', $report) }}">Download PDF</a>
            <a class="secondary" href="{{ route('reports.csv', $report) }}">Download CSV</a>
        </div>
    </header>

    <section class="panel report-readout">
        <h2>How to read the figures</h2>
        @forelse (data_get($report->snapshot, 'explanations', []) as $label => $text)
            <p><strong>{{ str_replace('_', ' ', ucfirst($label)) }}.</strong> {{ $text }}</p>
        @empty
            <p>This report was saved before the coverage definitions were stored. Generate it again to include them.</p>
        @endforelse
    </section>

    @php($surveyData = data_get($report->snapshot, 'survey'))
    @if (is_array($surveyData))
        <section class="panel">
            <h2>{{ $surveyData['name'] ?? 'Survey' }}</h2>
            <p>{{ $surveyData['location'] ?? '' }}</p>
            <div class="report-figures">
                <article>
                    <small>Good-signal coverage</small>
                    <b>{{ isset($surveyData['good_signal_coverage_percent']) ? number_format((float) $surveyData['good_signal_coverage_percent'], 1).'%' : '—' }}</b>
                </article>
                <article>
                    <small>Estimated coverage</small>
                    <b>{{ isset($surveyData['estimated_area_percent']) ? number_format((float) $surveyData['estimated_area_percent'], 1).'%' : '—' }}</b>
                </article>
                <article>
                    <small>Unmeasured area</small>
                    <b>{{ isset($surveyData['unmeasured_area_percent']) ? number_format((float) $surveyData['unmeasured_area_percent'], 1).'%' : '—' }}</b>
                </article>
            </div>
            @if (! empty($surveyData['coverage_definition']))
                <p>{{ $surveyData['coverage_definition'] }}</p>
            @endif
            @if (($surveyData['validation_mae_db'] ?? null) !== null)
                <p>Spatial holdout: MAE {{ $surveyData['validation_mae_db'] }} dB, RMSE {{ $surveyData['validation_rmse_db'] }} dB, bias {{ $surveyData['validation_bias_db'] }} dB.</p>
            @endif
        </section>
    @endif

    <details class="panel report-snapshot">
        <summary>Full data snapshot</summary>
        <pre>{{ json_encode($report->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </details>
</main>
</body>
</html>
