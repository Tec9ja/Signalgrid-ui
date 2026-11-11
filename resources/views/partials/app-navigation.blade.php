@auth
<a class="sg-skip-link" href="#main-content">Skip to main content</a>
<aside class="sg-app-sidebar" id="sgAppSidebar" aria-label="SignalGrid navigation">
    <a class="sg-side-brand" href="{{ route('dashboard') }}">
        <span class="sg-side-mark">SG</span>
        <span><b>SignalGrid</b><small>Campus Intelligence</small></span>
    </a>

    <nav class="sg-side-nav" aria-label="Primary">
        <p>Workspace</p>
        <a href="{{ route('dashboard') }}" @class(['active'=>request()->routeIs('dashboard')])><i data-lucide="layout-dashboard"></i><span>Overview</span></a>
        <a href="{{ route('locations.index') }}" @class(['active'=>request()->routeIs('locations.*','floor-plans.*')])><i data-lucide="building-2"></i><span>Locations</span></a>
        @can('locations.manage')<a href="{{ route('campus-setup.show') }}" @class(['active'=>request()->routeIs('campus-setup.*')])><i data-lucide="map-pinned"></i><span>Campus setup</span></a>@endcan
        <a href="{{ route('surveys.index') }}" @class(['active'=>request()->routeIs('surveys.*','survey-points.*','heatmaps.*')])><i data-lucide="scan-line"></i><span>RF survey studio</span></a>
        <a href="{{ route('measurements.index') }}" @class(['active'=>request()->routeIs('measurements.*')])><i data-lucide="activity"></i><span>Measurements</span></a>
        <a href="{{ route('access-points.index') }}" @class(['active'=>request()->routeIs('access-points.*')])><i data-lucide="router"></i><span>AP load analysis</span></a>
        <a href="{{ route('insights.index') }}" @class(['active'=>request()->routeIs('insights.*')])><i data-lucide="brain-circuit"></i><span>Explainable insights</span></a>
        <a href="{{ route('feedback.index') }}" @class(['active'=>request()->routeIs('feedback.index','feedback.correlate')])><i data-lucide="messages-square"></i><span>User feedback</span></a>
        <a href="{{ route('comparisons.index') }}" @class(['active'=>request()->routeIs('comparisons.*')])><i data-lucide="git-compare"></i><span>Comparisons</span></a>
        <a href="{{ route('reports.index') }}" @class(['active'=>request()->routeIs('reports.*')])><i data-lucide="file-chart-column"></i><span>Reports</span></a>

        <p>Research & management</p>
        @can('audit.view')<a href="{{ route('validation.index') }}" @class(['active'=>request()->routeIs('validation.*')])><i data-lucide="shield-check"></i><span>Validation</span></a>@endcan
        <a href="{{ route('project.alignment') }}" @class(['active'=>request()->routeIs('project.alignment*')])><i data-lucide="compass"></i><span>Project alignment</span></a>
        <a href="{{ route('settings.index') }}" @class(['active'=>request()->routeIs('settings.*')])><i data-lucide="settings-2"></i><span>Settings</span></a>
        @can('users.manage')<a href="{{ route('admin.index') }}" @class(['active'=>request()->routeIs('admin.*')])><i data-lucide="shield"></i><span>Administration</span></a>@endcan
    </nav>

    <div class="sg-side-user">
        @php
            $sideParts = preg_split('/\s+/', trim(auth()->user()->name)) ?: ['SG'];
            $sideInitials = strtoupper(mb_substr($sideParts[0], 0, 1).mb_substr(count($sideParts) > 1 ? $sideParts[array_key_last($sideParts)] : $sideParts[0], count($sideParts) > 1 ? 0 : 1, 1));
        @endphp
        <span>{{ $sideInitials }}</span>
        <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</small></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" aria-label="Sign out" title="Sign out"><i data-lucide="log-out"></i></button></form>
    </div>
</aside>
<button class="sg-side-scrim" type="button" data-side-scrim hidden aria-label="Close navigation"></button>
<button class="sg-side-toggle" type="button" aria-label="Open navigation" aria-controls="sgAppSidebar" aria-expanded="false" data-side-toggle>Menu</button>
@endauth
