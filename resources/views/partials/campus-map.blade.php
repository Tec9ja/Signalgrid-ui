@php($tile = $tile ?? null)
@php($mapsUrl = $mapsUrl ?? null)
@php($title = $title ?? 'Campus')
@php($interactive = $interactive ?? false)
<figure class="campus-map-card">
<div class="campus-map-frame">
<img class="campus-map-tile" data-map-tile alt="Map of {{ $title }}" @if($tile) src="https://tile.openstreetmap.org/{{ $tile['zoom'] }}/{{ $tile['x'] }}/{{ $tile['y'] }}.png" @else hidden @endif>
<span class="campus-pin" data-map-pin @if($tile) style="left: {{ $tile['pin_x'] }}%; top: {{ $tile['pin_y'] }}%" @else hidden @endif></span>
<p class="setup-map-empty" data-map-empty @if($tile) hidden @endif>{{ $empty ?? 'Enter a latitude and longitude to preview the campus.' }}</p>
</div>
<figcaption>
<small class="map-credit">Map data © OpenStreetMap</small>
<a @if($interactive) data-campus-maps-link @endif href="{{ $mapsUrl ?: 'https://www.google.com/maps' }}" target="_blank" rel="noopener noreferrer" @if(!$mapsUrl) hidden @endif>Open in Google Maps</a>
</figcaption>
</figure>
