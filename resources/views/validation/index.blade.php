<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Validation | SignalGrid</title>
    @vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')

<main id="main-content" class="domain-shell">
    <header class="domain-header">
        <div>
            <span class="eyebrow">SCIENTIFIC VALIDATION</span>
            <h1>Testing and research evaluation</h1>
            <p>IDW holdout validation, operational acceptance checks and reproducible evidence.</p>
        </div>
        <a class="secondary" href="{{ route('dashboard') }}">Dashboard</a>
    </header>

    <section class="panel validation-tools" aria-labelledby="validation-tools-title">
        <h2 id="validation-tools-title">Run a check</h2>

        <label>Survey
            <select id="validationSurvey">
                <option value="">Select survey</option>
                @foreach ($surveys as $survey)
                    <option value="{{ $survey->id }}">{{ $survey->name }}</option>
                @endforeach
            </select>
        </label>

        <label>Holdout %
            <input id="holdoutPercent" type="number" min="10" max="30" value="20">
        </label>

        <label>IDW power
            <input id="validationPower" type="number" min="1" max="5" step="0.1" value="2">
        </label>

        <button
            class="primary"
            id="runValidation"
            type="button"
            aria-label="Run IDW validation"
        >Run IDW validation</button>

        <button
            class="secondary"
            id="runAcceptance"
            type="button"
            aria-label="Run acceptance audit"
        >Run acceptance audit</button>

        <p id="validationMessage" role="status" aria-live="polite"></p>
    </section>

    <section class="validation-grid" aria-label="Scientific validation runs">
        @forelse ($validationRuns as $run)
            <article class="panel">
                <span class="survey-status completed">{{ strtoupper($run->method) }}</span>
                <h2>{{ $run->survey?->name ?? 'Deleted survey' }}</h2>
                <div>
                    <small>MAE</small>
                    <b>{{ $run->mean_absolute_error }} dB</b>
                </div>
                <div>
                    <small>RMSE</small>
                    <b>{{ $run->root_mean_square_error }} dB</b>
                </div>
                <div>
                    <small>Holdout</small>
                    <b>{{ $run->holdout_points }}/{{ $run->total_points }}</b>
                </div>
                <p>{{ $run->executed_at->format('d M Y H:i') }}</p>
            </article>
        @empty
            <div class="panel domain-empty">
                <h2>No validation runs</h2>
                <p>Select an eligible survey to create the first scientific validation run.</p>
            </div>
        @endforelse
    </section>

    <section class="panel" aria-labelledby="acceptance-title">
        <h2 id="acceptance-title">Latest operational audits</h2>
        @forelse ($acceptanceRuns as $run)
            <p>
                <b>{{ strtoupper($run->status) }}</b>
                · {{ $run->passed_checks }} passed
                · {{ $run->failed_checks }} failed
                · {{ $run->executed_at->format('d M Y H:i') }}
            </p>
        @empty
            <p>No operational acceptance audits have been recorded.</p>
        @endforelse
    </section>

    {{ $validationRuns->links() }}
</main>

<script>
window.validationConfig = {
    scientificBase: @json(url('/api/surveys')),
    acceptanceUrl: @json(route('validation.acceptance')),
    csrf: @json(csrf_token()),
};
</script>
</body>
</html>
