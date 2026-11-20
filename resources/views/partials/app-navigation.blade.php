@auth
<a class="sg-skip-link" href="#main-content">Skip to main content</a>
<aside class="sg-app-sidebar" id="sgAppSidebar" aria-label="SignalGrid navigation">
    <a class="sg-side-brand" href="{{ route('dashboard') }}">
        <span class="sg-side-mark">SG</span>
        <span><b>SignalGrid</b><small>Campus Intelligence</small></span>
    </a>

    <nav class="sg-side-nav" aria-label="Primary">
        @include('partials.sidebar-links')
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
