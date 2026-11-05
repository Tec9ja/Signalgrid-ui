@auth
<a class="sg-skip-link" href="#main-content">Skip to main content</a>
<div class="sg-global-nav" data-global-nav>
    <a class="sg-global-brand" href="{{ route('dashboard') }}" aria-label="SignalGrid dashboard">
        <span>SG</span><b>SignalGrid</b>
    </a>
    <button class="sg-nav-toggle" type="button" aria-expanded="false" aria-controls="sgGlobalLinks" data-nav-toggle>
        Menu
    </button>
    <nav id="sgGlobalLinks" class="sg-global-links" aria-label="Primary navigation" data-nav-links>
        <a href="{{ route('dashboard') }}" @class(['active'=>request()->routeIs('dashboard')])>Dashboard</a>
        <a href="{{ route('locations.index') }}" @class(['active'=>request()->routeIs('locations.*','floor-plans.*')])>Locations</a>
        <a href="{{ route('surveys.index') }}" @class(['active'=>request()->routeIs('surveys.*','survey-points.*','heatmaps.*')])>Surveys</a>
        <a href="{{ route('access-points.index') }}" @class(['active'=>request()->routeIs('access-points.*')])>AP load</a>
        <a href="{{ route('insights.index') }}" @class(['active'=>request()->routeIs('insights.*')])>Insights</a>
        <a href="{{ route('feedback.index') }}" @class(['active'=>request()->routeIs('feedback.index','feedback.correlate')])>Feedback</a>
        <a href="{{ route('comparisons.index') }}" @class(['active'=>request()->routeIs('comparisons.*')])>Comparisons</a>
        <a href="{{ route('reports.index') }}" @class(['active'=>request()->routeIs('reports.*')])>Reports</a>
        <a href="{{ route('validation.index') }}" @class(['active'=>request()->routeIs('validation.*')])>Validation</a>
        <a href="{{ route('project.alignment') }}" @class(['active'=>request()->routeIs('project.alignment*')])>Alignment</a>
        @can('users.manage')<a href="{{ route('admin.index') }}" @class(['active'=>request()->routeIs('admin.*')])>Admin</a>@endcan
    </nav>
    <form class="sg-global-logout" method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Sign out</button>
    </form>
</div>
@endauth
