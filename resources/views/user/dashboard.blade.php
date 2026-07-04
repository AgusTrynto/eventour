<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EvenTour</title>

    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @vite(['resources/css/user/dashboard.css', 'resources/js/app.js'])
</head>

<body class="dashboard-page">

    <div class="bg-glow"></div>

    {{-- NAVBAR (desktop) --}}
    <header class="navbar">
        <div class="container-custom">
            <button type="button" class="hamburger-btn" id="hamburger-btn" aria-label="Buka menu">
                <span></span><span></span><span></span>
            </button>

            <a href="/" class="logo">Even<span>Tour</span></a>

            <nav class="nav-links">
                <a href="/dashboard" class="nav-link active">Dashboard</a>
                <a href="#" class="nav-link">Event</a>
                <a href="{{ route('tickets.index') }}" class="nav-link">Tiket Saya</a>
                <a href="{{ route('reviews.index') }}" class="nav-link">Ulasan</a>
            </nav>

            <div class="nav-right">
                @if (auth()->user()->role === 'eo')
                    <a href="{{ route('eo.dashboard') }}" class="nav-link-eo-badge">
                        <x-icon name="building" :size="15" />
                        Dashboard EO
                    </a>
                @endif

                <span class="user-name">{{ $user->name }}</span>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout">Logout</button>
                </form>
            </div>
        </div>
    </header>

    {{-- SIDEBAR (mobile) --}}
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <aside class="mobile-sidebar" id="mobile-sidebar">
        <div class="sidebar-top">
            <a href="/" class="logo">Even<span>Tour</span></a>
            <button type="button" class="sidebar-close" id="sidebar-close" aria-label="Tutup menu">
                <x-icon name="x" :size="16" />
            </button>
        </div>

        <div class="sidebar-user">
            <span class="user-name">{{ $user->name }}</span>
        </div>

        <nav class="sidebar-nav">
            <a href="/dashboard" class="sidebar-link active">
                <x-icon name="bar-chart" :size="18" />
                Dashboard
            </a>
            <a href="#" class="sidebar-link">
                <x-icon name="ticket" :size="18" />
                Event
            </a>
            <a href="{{ route('tickets.index') }}" class="sidebar-link">
                <x-icon name="ticket" :size="18" />
                Tiket Saya
            </a>
            <a href="{{ route('reviews.index') }}" class="sidebar-link">
                <x-icon name="star" :size="18" />
                Ulasan
            </a>

            @if (auth()->user()->role === 'eo')
                <a href="{{ route('eo.dashboard') }}" class="sidebar-link sidebar-link-eo">
                    <x-icon name="building" :size="18" />
                    Dashboard EO
                </a>
            @endif
        </nav>

        <form action="{{ route('logout') }}" method="POST" class="sidebar-logout">
            @csrf
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </aside>

    <main class="main-content">
        <div class="container-custom">

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="alert alert-success">
                    <x-icon name="check-circle" :size="18" />
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            {{-- Welcome --}}
            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">DASHBOARD</span>
                    <h1>Halo, {{ $user->name }}</h1>
                    <p>Temukan event seru di sekitarmu dan jangan sampai ketinggalan.</p>
                </div>
            </div>

            {{-- Stats --}}
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="ticket" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Tiket Dimiliki</span>
                        <span class="stat-value">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="calendar" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Event Diikuti</span>
                        <span class="stat-value">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="map-pin" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Lokasi</span>
                        <span class="stat-value" id="location-status" style="font-size:14px; padding-top:4px;">
                            {{ session('user_location') ? 'Terdeteksi' : 'Mendeteksi...' }}
                        </span>
                    </div>
                    <button type="button" id="refresh-location-btn" class="btn-refresh-location" title="Perbarui lokasi">
                        <svg id="refresh-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                            <path d="M21 3v5h-5"/>
                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                            <path d="M8 16H3v5"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Content grid --}}
            <div class="content-grid">

                {{-- MAP CARD --}}
                <div class="card">
                    <div class="card-header">
                        <h2 id="map-title">Event di Sekitarmu</h2>
                        <div class="radius-control">
                            <label for="radius-select">Radius:</label>
                            <select id="radius-select" class="radius-select">
                                <option value="5000">5 km</option>
                                <option value="10000" selected>10 km</option>
                                <option value="25000">25 km</option>
                                <option value="50000">50 km</option>
                            </select>
                        </div>
                    </div>

                    <div class="map-toggle">
                        <button type="button" class="map-toggle-btn active" data-mode="events">
                            <x-icon name="ticket" :size="15" />
                            Event
                        </button>
                        <button type="button" class="map-toggle-btn" data-mode="eo">
                            <x-icon name="building" :size="15" />
                            Event Organizer
                        </button>
                    </div>

                    <div id="map" class="map-container"></div>

                    <div id="map-info" class="map-info">
                        <span id="map-info-text">Mendeteksi lokasi kamu...</span>
                    </div>
                </div>

                {{-- REKOMENDASI CARD --}}
                <div class="card">
                    <div class="card-header">
                        <h2>Rekomendasi untuk Anda</h2>
                        <a href="#" class="card-link">
                            Lihat semua
                            <x-icon name="arrow-right" size="14" />
                        </a>
                    </div>

                    <div class="recommendation-list" id="recommendation-list">
                        @forelse ($recommendedEvents as $event)
                            <div class="rec-item">
                                <div class="rec-icon"><x-icon name="ticket" :size="22" /></div>
                                <div class="rec-info">
                                    <span class="rec-title">{{ $event->title }}</span>
                                    <span class="rec-meta">
                                        {{ $event->location_name }} ·
                                        {{ $event->start_date->translatedFormat('d M Y') }}
                                    </span>
                                    <span class="rec-price">
                                        {{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}
                                    </span>
                                </div>
                                <a href="{{ route('events.show', $event->id) }}" class="rec-btn">Lihat</a>
                            </div>
                        @empty
                            <div class="empty-state">
                                <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
                                <p>Belum ada event tersedia saat ini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // ── Ambil lokasi dari session (kalau sudah ada) ─────────
        const savedLat = {{ session('user_location.lat', 'null') }};
        const savedLng = {{ session('user_location.lng', 'null') }};

        // ── Init map ────────────────────────────────────────────
        const defaultLat = -6.2088; // Jakarta sebagai fallback
        const defaultLng = 106.8456;

        const map = L.map('map', { zoomControl: true }).setView(
            [savedLat ?? defaultLat, savedLng ?? defaultLng],
            savedLat ? 12 : 5
        );

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: 19,
        }).addTo(map);

        // ── Marker & lingkaran radius ───────────────────────────
        let userMarker  = null;
        let radiusCircle = null;
        let dataMarkers = [];
        let currentMode = 'events'; // 'events' | 'eo'

        function getRadius() {
            return parseInt(document.getElementById('radius-select').value);
        }

        function placeUserOnMap(lat, lng) {
            if (userMarker) map.removeLayer(userMarker);
            if (radiusCircle) map.removeLayer(radiusCircle);

            userMarker = L.circleMarker([lat, lng], {
                radius: 8,
                fillColor: '#d8ff4f',
                color: '#0f1117',
                weight: 2,
                fillOpacity: 1,
            }).addTo(map).bindPopup('Lokasi kamu').openPopup();

            radiusCircle = L.circle([lat, lng], {
                radius: getRadius(),
                color: '#d8ff4f',
                fillColor: '#d8ff4f',
                fillOpacity: 0.06,
                weight: 1.5,
                dashArray: '6, 6',
            }).addTo(map);

            map.setView([lat, lng], 12);

            document.getElementById('map-info-text').textContent =
                `Memuat data dalam radius ${getRadius() / 1000} km...`;

            loadMapData();
        }

        function loadMapData() {
            dataMarkers.forEach(m => map.removeLayer(m));
            dataMarkers = [];

            const url = currentMode === 'events'
                ? `{{ route('events.nearby', [], false) }}?radius=${getRadius()}`
                : `{{ route('eo.nearby', [], false) }}?radius=${getRadius()}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (currentMode === 'events') {
                        renderEventMarkers(data.events || []);
                        updateMapInfo(data.count_total, data.count_in_radius, 'event');
                    } else {
                        renderEOMarkers(data.organizers || []);
                        updateMapInfo(data.count_total, data.count_in_radius, 'EO');
                    }
                })
                .catch(() => {
                    document.getElementById('map-info-text').textContent =
                        'Gagal memuat data.';
                });
        }

        function renderEventMarkers(events) {
            events.forEach(ev => {
                const inRadius = ev.in_radius === true || ev.in_radius === null;

                const marker = L.circleMarker([ev.lat, ev.lng], {
                    radius: inRadius ? 9 : 6,
                    fillColor: inRadius ? '#ff5da2' : '#9ca3af',
                    color: inRadius ? '#ffffff' : '#d1d5db',
                    weight: inRadius ? 2.5 : 1.5,
                    fillOpacity: inRadius ? 0.95 : 0.6,
                    opacity: inRadius ? 1 : 0.7,
                })
                .addTo(map)
                .bindPopup(
                    `<div class="popup-event">` +
                        `<strong>${ev.title}</strong><br>` +
                        `${ev.date}<br>` +
                        (ev.price > 0 ? `Rp ${Number(ev.price).toLocaleString('id-ID')}` : 'Gratis') +
                        (ev.distance !== null ? `<br><small>${(ev.distance / 1000).toFixed(1)} km dari kamu</small>` : '') +
                        `<br><a href="/events/${ev.id}" class="popup-link">Lihat Detail</a>` +
                    `</div>`
                );

                dataMarkers.push(marker);
            });
        }

        function renderEOMarkers(organizers) {
            organizers.forEach(eo => {
                const inRadius = eo.in_radius === true || eo.in_radius === null;
                const ratingText = eo.review_count > 0
                    ? `Rating ${Number(eo.average_rating).toFixed(1)} / 5 (${eo.review_count} ulasan)`
                    : 'Belum ada ulasan';

                const marker = L.circleMarker([eo.lat, eo.lng], {
                    radius: inRadius ? 9 : 6,
                    fillColor: inRadius ? '#60a5fa' : '#9ca3af',
                    color: inRadius ? '#ffffff' : '#d1d5db',
                    weight: inRadius ? 2.5 : 1.5,
                    fillOpacity: inRadius ? 0.95 : 0.6,
                    opacity: inRadius ? 1 : 0.7,
                })
                .addTo(map)
                .bindPopup(
                    `<div class="popup-event">` +
                        `<strong>${eo.name}</strong><br>` +
                        `${eo.total_events} event terdaftar<br>` +
                        `${ratingText}<br>` +
                        `Telepon: ${eo.phone}` +
                        (eo.distance !== null ? `<br><small>${(eo.distance / 1000).toFixed(1)} km dari kamu</small>` : '') +
                    `</div>`
                );

                dataMarkers.push(marker);
            });
        }

        function updateMapInfo(total, inRadius, label) {
            document.getElementById('map-info-text').textContent =
                `Menampilkan ${total} ${label}` +
                (inRadius !== null ? ` · ${inRadius} dalam radius ${getRadius() / 1000} km` : '');
        }

        // ── Toggle Event / EO ────────────────────────────────────
        document.querySelectorAll('.map-toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.map-toggle-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                currentMode = btn.dataset.mode;
                document.getElementById('map-title').textContent =
                    currentMode === 'events' ? 'Event di Sekitarmu' : 'Event Organizer di Sekitarmu';

                loadMapData();
            });
        });

        // ── Update radius saat select berubah ───────────────────
        document.getElementById('radius-select').addEventListener('change', () => {
            if (userMarker) {
                const latlng = userMarker.getLatLng();
                if (radiusCircle) map.removeLayer(radiusCircle);

                radiusCircle = L.circle(latlng, {
                    radius: getRadius(),
                    color: '#d8ff4f',
                    fillColor: '#d8ff4f',
                    fillOpacity: 0.06,
                    weight: 1.5,
                    dashArray: '6, 6',
                }).addTo(map);

                loadMapData();
            }
        });

        // ── Kalau sudah ada lokasi di session, langsung pakai ───
        if (savedLat && savedLng) {
            placeUserOnMap(savedLat, savedLng);
            document.getElementById('location-status').textContent = 'Terdeteksi';
        } else {
            requestUserLocation();
        }

        // ── Fungsi minta GPS dari browser ────────────────────────
        function requestUserLocation(onDone) {
            if (!('geolocation' in navigator)) {
                document.getElementById('location-status').textContent = 'Tidak didukung';
                loadMapData();
                if (onDone) onDone();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;

                    placeUserOnMap(lat, lng);
                    document.getElementById('location-status').textContent = 'Terdeteksi';

                    // Kirim ke server untuk disimpan di session
                    fetch('{{ route("location.save", [], false) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ lat, lng }),
                    }).finally(() => {
                        if (onDone) onDone();
                    });
                },
                (err) => {
                    document.getElementById('location-status').textContent = 'Ditolak';
                    document.getElementById('map-info-text').textContent =
                        'Izin lokasi ditolak. Aktifkan GPS untuk fitur ini.';
                    map.setView([defaultLat, defaultLng], 5);
                    loadMapData(); // tetap tampilkan data walau lokasi ditolak
                    if (onDone) onDone();
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }

        // ── Tombol refresh lokasi ─────────────────────────────────
        const refreshBtn  = document.getElementById('refresh-location-btn');
        const refreshIcon = document.getElementById('refresh-icon');

        refreshBtn.addEventListener('click', () => {
            refreshBtn.disabled = true;
            refreshIcon.classList.add('spinning');
            document.getElementById('location-status').textContent = 'Memperbarui...';
            document.getElementById('map-info-text').textContent = 'Mendeteksi lokasi kamu...';

            requestUserLocation(() => {
                refreshBtn.disabled = false;
                refreshIcon.classList.remove('spinning');
            });
        });

        // ── Sidebar mobile toggle ───────────────────────────
        const hamburgerBtn   = document.getElementById('hamburger-btn');
        const sidebarClose   = document.getElementById('sidebar-close');
        const mobileSidebar  = document.getElementById('mobile-sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            mobileSidebar.classList.add('open');
            sidebarOverlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            mobileSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        hamburgerBtn.addEventListener('click', openSidebar);
        sidebarClose.addEventListener('click', closeSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);
    </script>

</body>
</html>
