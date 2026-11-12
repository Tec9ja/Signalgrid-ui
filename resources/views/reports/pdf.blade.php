<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report->title }}</title>
    <style>
        @page { margin: 34px 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #182536; font-size: 10px; line-height: 1.45; }
        h1 { font-size: 22px; color: #087f73; margin: 6px 0 12px; }
        h2 { font-size: 14px; border-bottom: 1px solid #bdd7d3; padding-bottom: 4px; margin-top: 20px; }
        p { margin: 5px 0; }
        .brand { color: #087f73; font-size: 9px; font-weight: bold; letter-spacing: 1px; }
        .meta, .box { background: #eef7f5; padding: 10px; margin: 8px 0; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td, .grid th { border: 1px solid #d7e1e4; padding: 6px; text-align: left; vertical-align: top; }
        .grid th { width: 42%; background: #f6faf9; }
        .limitation { background: #fff6e7; padding: 9px; }
        .footer { margin-top: 25px; font-size: 8px; color: #667; border-top: 1px solid #d7e1e4; padding-top: 7px; }
    </style>
</head>
<body>
    <div class="brand">SIGNALGRID NETWORK INTELLIGENCE</div>
    <h1>{{ $report->title }}</h1>

    <div class="meta">
        <strong>{{ data_get($report->snapshot, 'project.institution', 'Institution unavailable') }}</strong><br>
        Reporting period: {{ $report->period_start->format('d M Y') }} to {{ $report->period_end->format('d M Y') }}<br>
        Generated: {{ $report->generated_at->format('d M Y H:i') }}<br>
        Methodology version: {{ $report->methodology_version }}
    </div>

    <h2>Project aim</h2>
    <p>{{ data_get($report->snapshot, 'project.aim', 'Not available') }}</p>

    @php($surveyData = data_get($report->snapshot, 'survey'))
    @if (is_array($surveyData))
        <h2>Survey summary</h2>
        <table class="grid">
            @foreach ($surveyData as $key => $value)
                @if (! is_array($value))
                    <tr>
                        <th>{{ str_replace('_', ' ', ucfirst($key)) }}</th>
                        <td>{{ $value === null || $value === '' ? 'Not available' : $value }}</td>
                    </tr>
                @endif
            @endforeach
        </table>

        <h2>Insights and recommendations</h2>
        @php($reportInsights = data_get($report->snapshot, 'survey.insights', []))
        @if (is_array($reportInsights) && count($reportInsights) > 0)
            @foreach ($reportInsights as $insight)
                <div class="box">
                    <strong>
                        {{ $insight['title'] ?? 'Insight' }}
                        · {{ strtoupper($insight['severity'] ?? 'unspecified') }}
                    </strong>
                    <p>{{ $insight['recommendation'] ?? 'No recommendation recorded.' }}</p>
                    @if (! empty($insight['limitations']))
                        <p><strong>Limitation:</strong> {{ implode(' ', array_filter($insight['limitations'])) }}</p>
                    @endif
                </div>
            @endforeach
        @else
            <p>No rule-based insights were generated for this scope.</p>
        @endif
    @endif

    @php($comparisonData = data_get($report->snapshot, 'comparison'))
    @if (is_array($comparisonData))
        <h2>Before-and-after comparison</h2>
        <table class="grid">
            @foreach (data_get($comparisonData, 'changes', []) as $key => $value)
                <tr>
                    <th>{{ str_replace('_', ' ', ucfirst($key)) }}</th>
                    <td>{{ $value === null ? 'Not comparable' : $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if (is_array($report->limitations) && count($report->limitations) > 0)
        <h2>Limitations</h2>
        <div class="limitation">
            @foreach ($report->limitations as $item)
                <p>• {{ $item }}</p>
            @endforeach
        </div>
    @endif

    <h2>How the figures are calculated</h2>
    @foreach (data_get($report->snapshot, 'explanations', []) as $explanation)
        <p>{{ $explanation }}</p>
    @endforeach
    @if (data_get($report->snapshot, 'survey.validation_mae_db') !== null)
        <p>
            This survey's spatial holdout recorded MAE {{ data_get($report->snapshot, 'survey.validation_mae_db') }} dB,
            RMSE {{ data_get($report->snapshot, 'survey.validation_rmse_db') }} dB
            and bias {{ data_get($report->snapshot, 'survey.validation_bias_db') }} dB.
        </p>
    @endif

    <h2>Data sources</h2>
    <p>{{ implode(', ', $report->data_sources ?? []) }}</p>
    <p>Measured means collected in the app. Imported means a CSV file. Estimated means a heat-map prediction. Simulated values are excluded from technical scores.</p>

    <h2>Methodology disclosure</h2>
    <p>
        Direct and imported measurements remain distinguishable from estimated heat-map regions.
        Simulated measurements are excluded from technical calculations. A feedback match uses location
        and time. It is not a statistical correlation. Recommendations are generated from documented
        deterministic rules and should be reviewed alongside the stated limitations.
    </p>

    <div class="footer">
        SignalGrid · {{ \App\Models\Institution::query()->orderBy('id')->value('name') ?: 'Adekunle Ajasin University, Akungba' }} · Report ID {{ $report->id }}
    </div>
</body>
</html>
