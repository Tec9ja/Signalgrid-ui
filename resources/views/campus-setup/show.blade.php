<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Campus setup | SignalGrid</title>
@vite('resources/js/app.js')
</head>
<body class="domain-body">
@include('partials.app-navigation')
<main id="main-content" class="domain-shell">
<header class="domain-header">
<div>
<span class="eyebrow">CAMPUS SETUP</span>
<h1>Campus record</h1>
<p>One institution for this workspace. Confirm the published facts, or replace them for another campus. Floors and measurements stay on the survey pages.</p>
</div>
<div class="header-links">
<a class="secondary" href="{{ route('locations.index') }}">Locations</a>
<a class="secondary" href="{{ route('dashboard') }}">Dashboard</a>
</div>
</header>

<div class="setup-paths">
<a href="{{ route('campus-setup.show', ['pack' => 'aaua']) }}" @class(['setup-path', 'is-active' => $mode === 'pack'])>
<span class="eyebrow">Published record</span>
<strong>Adekunle Ajasin University</strong>
<span>Postal address, campus coordinate and three named buildings. Nothing is saved until you confirm.</span>
</a>
<a href="{{ route('campus-setup.show', ['blank' => 1]) }}" @class(['setup-path', 'is-active' => $mode === 'blank'])>
<span class="eyebrow">Another campus</span>
<strong>Start from a blank record</strong>
<span>Saving replaces the institution and campus in this workspace. Buildings that already have floor plans, surveys, access points or feedback are kept.</span>
</a>
</div>

@if($mode === 'pack')
<p class="setup-callout" role="status"><strong>Review before saving.</strong> These fields are filled from the published AAUA record. Nothing is saved until you confirm.</p>
@elseif($mode === 'blank')
<p class="setup-callout" role="status"><strong>This replaces the current campus.</strong> Saving replaces the institution and campus in this workspace. Buildings that already have floor plans, surveys, access points or feedback are kept.</p>
@elseif($institution)
<p class="setup-callout" role="status"><strong>Prefilled from this workspace.</strong> Confirm to update the stored campus, load the AAUA public record, or start a different campus.</p>
@else
<p class="setup-callout" role="status"><strong>No campus record yet.</strong> Load the AAUA public record, or enter another campus.</p>
@endif

@if($errors->any())
<div class="setup-errors" role="alert">
<strong>Campus setup needs a correction.</strong>
<ul>
@foreach($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif

<form class="setup-layout" method="POST" action="{{ route('campus-setup.store') }}">
@csrf
<input type="hidden" name="retire_unlisted" value="1">

<section class="panel setup-card">
<header class="setup-card-head">
<span>01</span>
<div>
<h2>Institution</h2>
<p>The name on the dashboard, reports and location pages.</p>
</div>
</header>
<div class="setup-grid">
<label class="setup-field setup-span">Institution name
<input name="name" required maxlength="150" autocomplete="organization" value="{{ old('name', $form['name']) }}">
</label>
<label class="setup-field">Identifier
<input name="slug" required maxlength="80" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" autocapitalize="off" spellcheck="false" value="{{ old('slug', $form['slug']) }}">
<small>Lowercase letters, numbers and hyphens. Example: aaua-akungba.</small>
</label>
<label class="setup-field">Type
<select name="type" required>
@foreach(['university' => 'University', 'polytechnic' => 'Polytechnic', 'college' => 'College', 'research_institute' => 'Research institute', 'other' => 'Other'] as $value => $label)
<option value="{{ $value }}" @selected(old('type', $form['type']) === $value)>{{ $label }}</option>
@endforeach
</select>
</label>
<label class="setup-field">Timezone
<input name="timezone" required maxlength="64" value="{{ old('timezone', $form['timezone']) }}" list="timezone-list">
</label>
<datalist id="timezone-list">
<option value="Africa/Lagos"></option>
<option value="Africa/Accra"></option>
<option value="Africa/Nairobi"></option>
<option value="UTC"></option>
</datalist>
<label class="setup-field">Website
<input name="website" type="url" maxlength="255" placeholder="https://" inputmode="url" value="{{ old('website', $form['website']) }}">
</label>
<label class="setup-field setup-span">Motto
<input name="motto" maxlength="255" value="{{ old('motto', $form['motto']) }}">
</label>
</div>
</section>

<div class="setup-split">
<section class="panel setup-card">
<header class="setup-card-head">
<span>02</span>
<div>
<h2>Campus</h2>
<p>Postal address and the published coordinate. Leave the coordinate empty when it is not known.</p>
</div>
</header>
<div class="setup-grid">
<label class="setup-field">Campus name
<input name="campus_name" required maxlength="150" value="{{ old('campus_name', $form['campus_name']) }}">
</label>
<label class="setup-field">Campus code
<input name="campus_code" required maxlength="30" autocapitalize="characters" spellcheck="false" value="{{ old('campus_code', $form['campus_code']) }}">
</label>
<label class="setup-field setup-span">Postal address
<input name="address" maxlength="1000" autocomplete="street-address" value="{{ old('address', $form['address']) }}">
</label>
<label class="setup-field">Locality
<input name="locality" maxlength="150" value="{{ old('locality', $form['locality']) }}">
</label>
<label class="setup-field">Country
<input name="country" maxlength="80" autocomplete="country-name" value="{{ old('country', $form['country']) }}">
</label>
<label class="setup-field">Latitude
<input name="latitude" inputmode="decimal" value="{{ old('latitude', $form['latitude']) }}">
</label>
<label class="setup-field">Longitude
<input name="longitude" inputmode="decimal" value="{{ old('longitude', $form['longitude']) }}">
</label>
<label class="setup-field setup-span">How these facts were gathered
<select name="reference_source" required>
<option value="public_record" @selected(old('reference_source', $form['reference_source']) === 'public_record')>Public record</option>
<option value="administrator" @selected(old('reference_source', $form['reference_source']) === 'administrator')>Entered by an administrator</option>
</select>
</label>
<label class="setup-field setup-span">Note
<textarea name="setup_note" maxlength="2000" rows="5">{{ old('setup_note', $form['setup_note']) }}</textarea>
<small>Shown on the Locations page when someone asks where the record came from.</small>
</label>
</div>
</section>

<aside class="panel setup-map-card">
<header class="setup-card-head">
<span>Map</span>
<div>
<h2>Campus map</h2>
<p>The preview uses the coordinate beside it. It is not saved on its own.</p>
</div>
</header>
@php($previewLat = old('latitude', $form['latitude']))
@php($previewLng = old('longitude', $form['longitude']))
@include('partials.campus-map', [
    'tile' => \App\Support\MapTile::from($previewLat, $previewLng),
    'mapsUrl' => ($previewLat !== null && $previewLat !== '' && $previewLng !== null && $previewLng !== '') ? 'https://www.google.com/maps?q='.rawurlencode($previewLat.','.$previewLng) : null,
    'title' => old('campus_name', $form['campus_name']) ?: 'Campus',
    'interactive' => true,
])
<ul class="setup-kept">
<li>Interior floors are added after a site visit. A floor plan is optional.</li>
<li>Access points, measurements and feedback are not part of this record.</li>
</ul>
</aside>
</div>

<section class="panel setup-card">
<header class="setup-card-head">
<span>03</span>
<div>
<h2>Buildings</h2>
<p>Add only buildings you can name. A floor plan is not required. A building left off this list is removed only when it has no floor plan, survey, access point or feedback.</p>
</div>
<button class="secondary" type="button" data-add-building>Add building</button>
</header>
<div data-building-rows>
@foreach(old('buildings', $form['buildings']) as $index => $building)
<div class="setup-building">
<div class="setup-building-top">
<span>Building {{ $index + 1 }}</span>
<button class="secondary" type="button" data-remove-building>Remove</button>
</div>
<div class="setup-grid">
<label class="setup-field">Name
<input name="buildings[{{ $index }}][name]" maxlength="150" value="{{ $building['name'] ?? '' }}">
</label>
<label class="setup-field">Code
<input name="buildings[{{ $index }}][code]" maxlength="30" autocapitalize="characters" spellcheck="false" value="{{ $building['code'] ?? '' }}">
</label>
<label class="setup-field setup-span">Published note
<input name="buildings[{{ $index }}][description]" maxlength="1000" value="{{ $building['description'] ?? '' }}">
</label>
</div>
</div>
@endforeach
</div>
<template id="buildingRowTemplate">
<div class="setup-building">
<div class="setup-building-top">
<span>New building</span>
<button class="secondary" type="button" data-remove-building>Remove</button>
</div>
<div class="setup-grid">
<label class="setup-field">Name
<input name="buildings[__INDEX__][name]" maxlength="150">
</label>
<label class="setup-field">Code
<input name="buildings[__INDEX__][code]" maxlength="30" autocapitalize="characters" spellcheck="false">
</label>
<label class="setup-field setup-span">Published note
<input name="buildings[__INDEX__][description]" maxlength="1000">
</label>
</div>
</div>
</template>
</section>

<div class="setup-savebar">
<p>Saving updates this workspace. You can open Campus setup again whenever the record changes.</p>
<button class="primary" type="submit">{{ $mode === 'pack' ? 'Save the AAUA public record' : 'Save campus setup' }}</button>
</div>
</form>
</main>
<script>
(function () {
    var lat = document.querySelector('[name="latitude"]');
    var lng = document.querySelector('[name="longitude"]');
    var tile = document.querySelector('[data-map-tile]');
    var pin = document.querySelector('[data-map-pin]');
    var empty = document.querySelector('[data-map-empty]');
    var link = document.querySelector('[data-campus-maps-link]');
    var name = document.querySelector('[name="name"]');
    var slug = document.querySelector('[name="slug"]');
    var slugEdited = slug && slug.value.trim() !== '';
    var host = document.querySelector('[data-building-rows]');
    var template = document.querySelector('#buildingRowTemplate');
    var nextIndex = host ? host.querySelectorAll('.setup-building').length : 0;

    function refreshMap() {
        if (!lat || !lng || !tile || !pin || !link || !empty) return;
        var la = Number(lat.value);
        var ln = Number(lng.value);
        var valid = lat.value.trim() !== '' && lng.value.trim() !== '' && Number.isFinite(la) && Number.isFinite(ln) && la >= -85 && la <= 85 && ln >= -180 && ln <= 180;
        tile.hidden = !valid;
        pin.hidden = !valid;
        empty.hidden = valid;
        link.hidden = !valid;
        if (!valid) {
            tile.removeAttribute('src');
            return;
        }
        var zoom = 16;
        var n = Math.pow(2, zoom);
        var xFloat = (ln + 180) / 360 * n;
        var latRad = la * Math.PI / 180;
        var yFloat = (1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2 * n;
        tile.src = 'https://tile.openstreetmap.org/' + zoom + '/' + Math.floor(xFloat) + '/' + Math.floor(yFloat) + '.png';
        pin.style.left = ((xFloat - Math.floor(xFloat)) * 100) + '%';
        pin.style.top = ((yFloat - Math.floor(yFloat)) * 100) + '%';
        link.href = 'https://www.google.com/maps?q=' + encodeURIComponent(la + ',' + ln);
    }

    if (lat) lat.addEventListener('input', refreshMap);
    if (lng) lng.addEventListener('input', refreshMap);
    refreshMap();

    if (slug) slug.addEventListener('input', function () { slugEdited = true; });
    if (name && slug) {
        name.addEventListener('input', function () {
            if (slugEdited) return;
            slug.value = name.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        });
    }

    document.querySelector('[data-add-building]')?.addEventListener('click', function () {
        if (!host || !template) return;
        host.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex)));
        nextIndex += 1;
    });
    host?.addEventListener('click', function (event) {
        var button = event.target.closest('[data-remove-building]');
        if (!button || host.querySelectorAll('.setup-building').length < 2) return;
        button.closest('.setup-building')?.remove();
    });
})();
</script>
</body>
</html>
