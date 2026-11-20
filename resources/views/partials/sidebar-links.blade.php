@php($navClass = $navClass ?? null)
<div class="nav-group">
    <p class="nav-label">Network</p>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('dashboard')])) href="{{ route('dashboard') }}"><i data-lucide="layout-dashboard"></i><span>Overview</span></a>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('measurements.*')])) href="{{ route('measurements.index') }}"><i data-lucide="activity"></i><span>Measurements</span></a>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('access-points.*')])) href="{{ route('access-points.index') }}"><i data-lucide="router"></i><span>AP load analysis</span></a>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('insights.*')])) href="{{ route('insights.index') }}"><i data-lucide="brain-circuit"></i><span>Explainable insights</span></a>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('feedback.index', 'feedback.correlate')])) href="{{ route('feedback.index') }}"><i data-lucide="messages-square"></i><span>User feedback</span></a>
</div>
<div class="nav-group">
    <p class="nav-label">Surveys</p>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('surveys.*', 'survey-points.*', 'heatmaps.*')])) href="{{ route('surveys.index') }}"><i data-lucide="scan-line"></i><span>RF survey studio</span></a>
    <a @class(array_filter([$navClass])) href="{{ route('surveys.index') }}#coverage"><i data-lucide="map"></i><span>Coverage map</span></a>
    <a @class(array_filter([$navClass])) href="{{ route('surveys.index') }}#import-measurements"><i data-lucide="database-zap"></i><span>Data sources</span></a>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('locations.*', 'floor-plans.*')])) href="{{ route('locations.index') }}"><i data-lucide="building-2"></i><span>Locations</span></a>
</div>
<div class="nav-group">
    <p class="nav-label">Reports</p>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('reports.*')])) href="{{ route('reports.index') }}"><i data-lucide="file-chart-column"></i><span>Reports</span></a>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('comparisons.*')])) href="{{ route('comparisons.index') }}"><i data-lucide="git-compare"></i><span>Comparisons</span></a>
</div>
<div class="nav-group">
    <p class="nav-label">Workspace</p>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('project.alignment*')])) href="{{ route('project.alignment') }}"><i data-lucide="compass"></i><span>Project alignment</span></a>
    <a @class(array_filter([$navClass, 'active' => request()->routeIs('settings.*')])) href="{{ route('settings.index') }}"><i data-lucide="settings-2"></i><span>Settings</span></a>
</div>
@canany(['locations.manage', 'audit.view', 'users.manage'])
    <div class="nav-group">
        <p class="nav-label">Admin</p>
        @can('locations.manage')
            <a @class(array_filter([$navClass, 'active' => request()->routeIs('campus-setup.*')])) href="{{ route('campus-setup.show') }}"><i data-lucide="map-pinned"></i><span>Campus setup</span></a>
        @endcan
        @can('audit.view')
            <a @class(array_filter([$navClass, 'active' => request()->routeIs('validation.*')])) href="{{ route('validation.index') }}"><i data-lucide="shield-check"></i><span>Validation</span></a>
        @endcan
        @can('users.manage')
            <a @class(array_filter([$navClass, 'active' => request()->routeIs('admin.*')])) href="{{ route('admin.index') }}"><i data-lucide="shield"></i><span>Administration</span></a>
        @endcan
    </div>
@endcanany
