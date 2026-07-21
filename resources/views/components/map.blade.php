@props(['markers', 'id' => 'map', 'zoom' => 12, 'height' => 'h-96'])

@php
    $markers = collect($markers)->filter(fn ($m) => $m['lat'] !== null && $m['lng'] !== null)->values();
@endphp

@if ($markers->isNotEmpty())
    <div id="{{ $id }}" class="{{ $height }} w-full rounded-3xl overflow-hidden ring-1 ring-sand-200" role="img" aria-label="Standortkarte"></div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var markers = {!! $markers->toJson() !!};
                var mapEl = document.getElementById('{{ $id }}');
                if (!mapEl || typeof L === 'undefined' || markers.length === 0) return;

                var map = L.map('{{ $id }}', { scrollWheelZoom: false });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> Mitwirkende',
                    maxZoom: 18,
                }).addTo(map);

                var useCluster = typeof L.markerClusterGroup === 'function' && markers.length > 1;
                var clusterGroup = useCluster ? L.markerClusterGroup({ maxClusterRadius: 50 }) : null;

                var bounds = [];
                markers.forEach(function (m) {
                    var marker = L.marker([m.lat, m.lng]);
                    var popupHtml = '<div style="min-width:180px">' +
                        (m.image ? '<img src="' + m.image + '" alt="" style="width:100%;height:90px;object-fit:cover;border-radius:8px;margin-bottom:6px">' : '') +
                        '<strong style="display:block;margin-bottom:2px">' + m.title + '</strong>' +
                        (m.category ? '<span style="font-size:12px;color:#5f9070">' + m.category + '</span><br>' : '') +
                        (m.url ? '<a href="' + m.url + '" style="font-size:12px;color:#2f5c3f;text-decoration:underline">Zum Reisetipp</a>' : '') +
                        '</div>';
                    marker.bindPopup(popupHtml);

                    if (useCluster) {
                        clusterGroup.addLayer(marker);
                    } else {
                        marker.addTo(map);
                    }
                    bounds.push([m.lat, m.lng]);
                });

                if (useCluster) {
                    map.addLayer(clusterGroup);
                }

                if (bounds.length > 1) {
                    map.fitBounds(bounds, { padding: [30, 30] });
                } else {
                    map.setView(bounds[0], {{ $zoom }});
                }
            });
        </script>
    @endpush
@endif
