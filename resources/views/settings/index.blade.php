<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Settings | SignalGrid</title>
@vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
<header class="domain-header">
<div>
<span class="eyebrow">WORKSPACE</span>
<h1>Settings</h1>
<p>Notification delivery for {{ auth()->user()->email }}.</p>
</div>
<div class="header-links">
@can('locations.manage')<a class="secondary" href="{{ route('campus-setup.show') }}">Campus setup</a>@endcan
<a class="secondary" href="{{ route('project.alignment') }}">Project alignment</a>
@can('users.manage')<a class="secondary" href="{{ route('admin.index') }}">Administration</a>@endcan
</div>
</header>
<section class="panel">
<div class="settings-head"><b>Event</b><b>In app</b><b>Email</b><b>Save</b></div>
@foreach($events as $key => $label)
@php($preference = $preferences->get($key))
<div class="settings-row" data-event="{{ $key }}">
<span>{{ $label }}</span>
<label><input data-database type="checkbox" aria-label="In app for {{ $label }}" @checked(optional($preference)->database_enabled ?? true)><span>In app</span></label>
<label><input data-mail type="checkbox" aria-label="Email for {{ $label }}" @checked(optional($preference)->mail_enabled ?? false)><span>Email</span></label>
<button class="secondary" type="button" data-preference="{{ route('notifications.preferences') }}">Save</button>
</div>
@endforeach
<p id="settingsMessage"></p>
</section>
</main>
</body>
</html>
