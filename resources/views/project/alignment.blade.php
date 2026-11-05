<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Project Alignment | SignalGrid</title>
    @vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
    <header class="domain-header">
        <div>
            <span class="eyebrow">ACADEMIC SPECIFICATION</span>
            <h1>Project alignment audit</h1>
            <p>{{ data_get($audit, 'profile.project_title', 'AAUA project profile has not been configured yet.') }}</p>
        </div>
        <a class="secondary" href="{{ route('dashboard') }}">Dashboard</a>
    </header>

    @if (data_get($audit, 'profile'))
        <section class="panel alignment-summary">
            <div>
                <b>{{ $audit['passed'] }}/{{ $audit['total'] }}</b>
                <span>alignment checks passed</span>
            </div>
            <p>{{ data_get($audit, 'profile.aim') }}</p>
        </section>

        <section class="alignment-grid">
            @foreach ($audit['checks'] as $check)
                <article class="panel {{ $check['passed'] ? 'align-pass' : 'align-fail' }}">
                    <span>{{ $check['passed'] ? '✓' : '!' }}</span>
                    <div>
                        <b>{{ str_replace('_', ' ', ucfirst($check['key'])) }}</b>
                        <p>{{ $check['detail'] }}</p>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="panel">
            <h3>Approved objectives</h3>
            <ol>
                @foreach (data_get($audit, 'profile.objectives', []) as $objective)
                    <li>{{ $objective }}</li>
                @endforeach
            </ol>
            <h3>Scope</h3>
            <p>{{ implode(' · ', data_get($audit, 'profile.scope', [])) }}</p>
            <h3>Limitations</h3>
            <p>{{ implode(' · ', data_get($audit, 'profile.limitations', [])) }}</p>
        </section>
    @else
        <section class="panel domain-empty">
            <h2>Project profile unavailable</h2>
            <p>Run <code>php artisan db:seed --class=ProjectAlignmentSeeder</code> to configure the AAUA research profile.</p>
        </section>
    @endif
</main>
</body>
</html>
