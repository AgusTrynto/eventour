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

    {{-- NAVBAR --}}
    <header class="navbar">
        <div class="container-custom">
            <a href="/" class="logo">Even<span>Tour</span></a>

            <nav class="nav-links">
                <a href="/dashboard" class="nav-link active">Dashboard</a>
                <a href="#" class="nav-link">Event</a>
                <a href="#" class="nav-link">Tiket Saya</a>
            </nav>

            <div class="nav-right">
                <span class="user-name">{{ $user->name }}</span>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container-custom">

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            {{-- Welcome --}}
            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">DASHBOARD</span>
                    <h1>Halo, {{ $user->name }} 👋</h1>
                    <p>Temukan event seru di sekitarmu dan jangan sampai ketinggalan.</p>
                </div>
            </div>

            {{-- Stats --}}
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎟️</div>
                    <div class="stat-info">
                        <span class="stat-label">Tiket Dimiliki</span>
                        <span class="stat-value">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <span class="stat-label">Event Diikuti</span>
                        <span class="stat-value">0</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📍</div>
                    <div class="stat-info">
                        <span class="stat-label">Lokasi</span>
                        <span class="stat-value" id="location-status" style="font-size:14px; padding-top:4px;">
                            {{ session('user_location') ? '✅ Terdeteksi' : '⏳ Mendeteksi...' }}
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
                        <h2>Event di Sekitarmu</h2>
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

                    <div id="map" class="map-container"></div>

                    <div id="map-info" class="map-info">
                        <span id="map-info-text">🔍 Mendeteksi lokasi kamu...</span>
                    </div>
                </div>

                {{-- REKOMENDASI CARD --}}
                <div class="card">
                    <div class="card-header">
                        <h2>Rekomendasi untuk Anda</h2>
                        <a href="#" class="card-link">Lihat semua →</a>
                    </div>

                    <div class="recommendation-list" id="recommendation-list">
                        {{-- Contoh item statis, nanti diganti dari DB --}}
                        <div class="rec-item">
                            <div class="rec-emoji">🎵</div>
                            <div class="rec-info">
                                <span class="rec-title">Java Jazz Festival 2026</span>
                                <span class="rec-meta">Jakarta · 15 Jun 2026</span>
                                <span class="rec-price">Rp 250.000</span>
                            </div>
                            <a href="#" class="rec-btn">Lihat</a>
                        </div>

                        <div class="rec-item">
                            <div class="rec-emoji">🎭</div>
                            <div class="rec-info">
                                <span class="rec-title">Pameran Seni Nusantara</span>
                                <span class="rec-meta">Semarang · 20 Jun 2026</span>
                                <span class="rec-price">Rp 50.000</span>
                            </div>
                            <a href="#" class="rec-btn">Lihat</a>
                        </div>

                        <div class="rec-item">
                            <div class="rec-emoji">🏃</div>
                            <div class="rec-info">
                                <span class="rec-title">Marathon Borobudur 2026</span>
                                <span class="rec-meta">Magelang · 1 Jul 2026</span>
                                <span class="rec-price">Rp 150.000</span>
                            </div>
                            <a href="#" class="rec-btn">Lihat</a>
                        </div>

                        <div class="rec-item">
                            <div class="rec-emoji">🍜</div>
                            <div class="rec-info">
                                <span class="rec-title">Festival Kuliner Jawa Tengah</span>
                                <span class="rec-meta">Semarang · 5 Jul 2026</span>
                                <span class="rec-price">Gratis</span>
                            </div>
                            <a href="#" class="rec-btn">Lihat</a>
                        </div>

                        <div class="rec-item">
                            <div class="rec-emoji">🎮</div>
                            <div class="rec-info">
                                <span class="rec-title">GameFest Indonesia 2026</span>
                                <span class="rec-meta">Surabaya · 12 Jul 2026</span>
                                <span class="rec-price">Rp 100.000</span>
                            </div>
                            <a href="#" class="rec-btn">Lihat</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer>© 2026 EvenTour. All Rights Reserved.</footer>

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
            }).addTo(map).bindPopup('📍 Lokasi kamu').openPopup();

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
                `📍 Menampilkan event dalam radius ${getRadius() / 1000} km dari lokasi kamu`;
        }

        // ── Update radius saat select berubah ───────────────────
        document.getElementById('radius-select').addEventListener('change', () => {
            if (userMarker) {
                const latlng = userMarker.getLatLng();
                placeUserOnMap(latlng.lat, latlng.lng);
            }
        });

        // ── Kalau sudah ada lokasi di session, langsung pakai ───
        if (savedLat && savedLng) {
            placeUserOnMap(savedLat, savedLng);
            document.getElementById('location-status').textContent = '✅ Terdeteksi';
        } else {
            requestUserLocation();
        }

        // ── Fungsi minta GPS dari browser ────────────────────────
        function requestUserLocation(onDone) {
            if (!('geolocation' in navigator)) {
                document.getElementById('location-status').textContent = '❌ Tidak didukung';
                if (onDone) onDone();
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;

                    placeUserOnMap(lat, lng);
                    document.getElementById('location-status').textContent = '✅ Terdeteksi';

                    // Kirim ke server untuk disimpan di session
                    fetch('{{ route("location.save") }}', {
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
                    document.getElementById('location-status').textContent = '❌ Ditolak';
                    document.getElementById('map-info-text').textContent =
                        '⚠️ Izin lokasi ditolak. Aktifkan GPS untuk fitur ini.';
                    map.setView([defaultLat, defaultLng], 5);
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
            document.getElementById('location-status').textContent = '⏳ Memperbarui...';
            document.getElementById('map-info-text').textContent = '🔍 Mendeteksi lokasi kamu...';

            requestUserLocation(() => {
                refreshBtn.disabled = false;
                refreshIcon.classList.remove('spinning');
            });
        });
    </script>

</body>
</html>