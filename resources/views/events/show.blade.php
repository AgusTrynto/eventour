<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->title }} - EvenTour</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @vite(['resources/css/event/show.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="event-detail-page">

    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'events'])

    <main class="main-content">
        <div class="container-custom narrow">

            {{-- HEADER EVENT --}}
            <div class="event-header">
                <span class="category-badge">{{ $event->category ?? 'Event' }}</span>
                <span class="event-state-badge {{ $event->is_ended ? 'ended' : 'active' }}">
                    {{ $event->display_status }}
                </span>
                <h1>{{ $event->title }}</h1>

                <div class="event-meta-row">
                    <div class="meta-item">
                        <span class="meta-icon"><x-icon name="calendar" :size="22" /></span>
                        <div>
                            <span class="meta-label">Tanggal</span>
                            <span class="meta-value">
                                {{ $event->start_date?->translatedFormat('l, d F Y, H:i') ?? '-' }} WIB
                                @if ($event->end_date)
                                    <br><span class="meta-sub">s/d {{ $event->end_date->translatedFormat('l, d F Y, H:i') }} WIB</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="meta-item">
                        <span class="meta-icon"><x-icon name="map-pin" :size="22" /></span>
                        <div>
                            <span class="meta-label">Lokasi</span>
                            <span class="meta-value">{{ $event->location_name }}</span>
                        </div>
                    </div>

                    <div class="meta-item">
                        <span class="meta-icon"><x-icon name="building" :size="22" /></span>
                        <div>
                            <span class="meta-label">Diselenggarakan oleh</span>
                            <span class="meta-value">{{ $event->organizer->org_name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('error'))
                <div class="detail-alert detail-alert-error">{{ session('error') }}</div>
            @endif

            <div class="content-layout">

                {{-- KIRI: deskripsi + map --}}
                <div class="content-main">

                    <div class="card">
                        <h2>Tentang Event</h2>
                        <p class="event-description">
                            {{ $event->description ?? 'Belum ada deskripsi untuk event ini.' }}
                        </p>
                    </div>

                    <div class="card">
                        <h2>Lokasi</h2>
                        <p class="location-name">{{ $event->location_name }}</p>
                        <div id="map" class="map-container"></div>
                        <div id="route-info" class="route-info">
                            {{ $userLocation ? 'Memuat jalur terdekat...' : 'Lokasi kamu belum tersedia. Perbarui lokasi dari dashboard untuk melihat jalur.' }}
                        </div>
                    </div>

                </div>

                {{-- KANAN: kartu beli tiket (sticky) --}}
                <div class="content-sidebar">
                    <div class="ticket-card">
                        <div class="ticket-price">
                            <span class="price-label">Harga Tiket</span>
                            <span class="price-value">
                                {{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}
                            </span>
                        </div>

                        <div class="ticket-info-row">
                            <span>Kuota</span>
                            <span>{{ $event->quota ? $event->quota . ' tiket' : 'Tidak terbatas' }}</span>
                        </div>

                        @if ($event->quota)
                            <div class="ticket-info-row">
                                <span>Tersisa</span>
                                <span class="{{ ($event->quota - $event->tickets_sold) <= 10 ? 'low-stock' : '' }}">
                                    {{ max(0, $event->quota - $event->tickets_sold) }} tiket
                                </span>
                            </div>
                        @endif

                        <div class="escrow-note">
                            <x-icon name="shield" :size="16" />
                            Dana kamu ditahan aman oleh EvenTour sampai event terverifikasi berlangsung.
                        </div>

                        @auth
                            @if ($event->is_ended)
                                <button class="btn-buy" disabled>Event Berakhir</button>
                            @elseif ($event->quota !== null && ($event->quota - $event->tickets_sold) <= 0)
                                <button class="btn-buy" disabled>Tiket Habis</button>
                            @else
                                <a href="{{ route('checkout.show', $event) }}" class="btn-buy">
                                    {{ $event->price > 0 ? 'Beli Tiket' : 'Klaim Tiket Gratis' }}
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn-buy">
                                Login untuk Beli Tiket
                            </a>
                        @endauth
                    </div>
                </div>

            </div>

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const eventLat = {{ $event->lat }};
        const eventLng = {{ $event->lng }};
        const eventTitle = @json($event->title);
        const userLocation = @json($userLocation);
        const routeInfo = document.getElementById('route-info');
        const motorOsrmProfile = 'cycling';
        const normalMotorSpeedKmh = 35;
        const routeColor = '#d8ff4f';

        let routeData = null;
        let currentRouteLine = null;
        let currentRouteIsFallback = false;

        function hasValidCoordinates(lat, lng) {
            return Number.isFinite(lat)
                && Number.isFinite(lng)
                && lat >= -90
                && lat <= 90
                && lng >= -180
                && lng <= 180;
        }

        function formatDistanceMeters(distance) {
            if (!Number.isFinite(distance)) return null;

            if (distance < 1000) {
                return `${Math.round(distance)} m`;
            }

            return `${(distance / 1000).toFixed(distance < 10000 ? 1 : 0)} km`;
        }

        function formatDurationSeconds(seconds) {
            if (!Number.isFinite(seconds)) return null;

            const minutes = Math.max(1, Math.round(seconds / 60));

            if (minutes < 60) {
                return `${minutes} menit`;
            }

            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;

            return remainingMinutes ? `${hours} jam ${remainingMinutes} menit` : `${hours} jam`;
        }

        function directDistanceMeters(fromLat, fromLng, toLat, toLng) {
            const earthRadius = 6371000;
            const toRadians = degrees => degrees * Math.PI / 180;
            const deltaLat = toRadians(toLat - fromLat);
            const deltaLng = toRadians(toLng - fromLng);
            const startLat = toRadians(fromLat);
            const endLat = toRadians(toLat);
            const a = Math.sin(deltaLat / 2) ** 2
                + Math.cos(startLat) * Math.cos(endLat) * Math.sin(deltaLng / 2) ** 2;

            return 2 * earthRadius * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function estimatedMotorDuration(distanceMeters) {
            if (!Number.isFinite(distanceMeters)) {
                return null;
            }

            return (distanceMeters / 1000) / normalMotorSpeedKmh * 3600;
        }

        const map = L.map('map', {
            zoomControl: true,
            scrollWheelZoom: false,
        }).setView([eventLat, eventLng], 15);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: 19,
        }).addTo(map);

        const eventMarker = L.circleMarker([eventLat, eventLng], {
            radius: 10,
            fillColor: '#ff5da2',
            color: '#ffffff',
            weight: 2.5,
            fillOpacity: 0.95,
        }).addTo(map).bindPopup(`<strong>${eventTitle}</strong>`).openPopup();

        if (userLocation && hasValidCoordinates(Number(userLocation.lat), Number(userLocation.lng))) {
            const userLat = Number(userLocation.lat);
            const userLng = Number(userLocation.lng);
            const userMarker = L.circleMarker([userLat, userLng], {
                radius: 8,
                fillColor: '#d8ff4f',
                color: '#0f1117',
                weight: 2,
                fillOpacity: 1,
            }).addTo(map).bindPopup('Lokasi kamu');

            const renderFallbackLine = () => {
                if (currentRouteLine) {
                    map.removeLayer(currentRouteLine);
                }

                routeData = null;
                currentRouteIsFallback = true;
                currentRouteLine = L.polyline([[userLat, userLng], [eventLat, eventLng]], {
                    color: routeColor,
                    dashArray: '8, 8',
                    opacity: 0.78,
                    weight: 3,
                }).addTo(map);

                map.fitBounds(L.featureGroup([userMarker, eventMarker, currentRouteLine]).getBounds(), {
                    padding: [36, 36],
                    maxZoom: 14,
                });

                updateRouteInfo();
            };

            const renderRoute = () => {
                if (!routeData?.latLngs?.length) {
                    renderFallbackLine();
                    return;
                }

                if (currentRouteLine) {
                    map.removeLayer(currentRouteLine);
                }

                currentRouteIsFallback = false;
                currentRouteLine = L.polyline(routeData.latLngs, {
                    color: routeColor,
                    opacity: 0.92,
                    weight: 4,
                }).addTo(map);

                map.fitBounds(currentRouteLine.getBounds(), {
                    padding: [36, 36],
                    maxZoom: 14,
                });

                updateRouteInfo();
            };

            const updateRouteInfo = () => {
                const distance = routeData?.distance
                    ?? directDistanceMeters(userLat, userLng, eventLat, eventLng);
                const duration = estimatedMotorDuration(distance);
                const distanceLabel = formatDistanceMeters(distance);
                const durationLabel = formatDurationSeconds(duration);
                const routeLabel = currentRouteIsFallback ? 'garis arah langsung' : 'jalur terdekat';

                routeInfo.textContent = `Motor: ${distanceLabel ?? '-'} - ${durationLabel ?? '-'} (${routeLabel}, perkiraan kecepatan normal)`;
            };

            const routeUrl = () => {
                const params = new URLSearchParams({
                    alternatives: 'false',
                    steps: 'true',
                    annotations: 'true',
                    overview: 'full',
                    geometries: 'geojson',
                });

                return `https://router.project-osrm.org/route/v1/${motorOsrmProfile}/${userLng},${userLat};${eventLng},${eventLat}?${params.toString()}`;
            };

            const loadMotorRoute = () => {
                routeData = null;
                currentRouteIsFallback = false;
                routeInfo.textContent = 'Motor: memuat jalur...';

                fetch(routeUrl())
                    .then(response => {
                        if (!response.ok) throw new Error('Gagal memuat rute.');

                        return response.json();
                    })
                    .then(data => {
                        const route = data.routes?.[0];
                        const coordinates = route?.geometry?.coordinates;

                        if (!Array.isArray(coordinates) || !coordinates.length) {
                            renderFallbackLine();
                            return;
                        }

                        routeData = {
                            distance: route.distance,
                            latLngs: coordinates.map(([lng, lat]) => [lat, lng]),
                        };
                        renderRoute();
                    })
                    .catch(renderFallbackLine);
            };

            loadMotorRoute();
        }
    </script>

</body>
</html>
