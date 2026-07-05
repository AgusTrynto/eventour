<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EvenTour</title>

    {{-- Leaflet CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @vite(['resources/css/user/dashboard.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="dashboard-page">

    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'dashboard', 'user' => $user])

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

            {{-- Search & filters --}}
            <div class="event-search-panel" id="event-search">
                <div class="search-main">
                    <div class="search-input-wrap">
                        <x-icon name="search" :size="18" />
                        <input
                            type="search"
                            id="event-search-input"
                            class="event-search-input"
                            placeholder="Cari nama event atau lokasi"
                            autocomplete="off"
                            aria-label="Cari event"
                            aria-controls="event-search-suggestions"
                        >
                        <button type="button" id="event-search-clear" class="search-clear-btn" aria-label="Bersihkan pencarian" title="Bersihkan pencarian" hidden>
                            <x-icon name="x" :size="16" />
                        </button>
                    </div>

                </div>

                <div class="filter-row">
                    <div class="price-range-field">
                        <div class="price-range-header">
                            <label for="event-price-range">Range harga</label>
                            <span id="event-price-range-label">
                                Rp 0 - {{ $eventPriceMax > 0 ? 'Rp ' . number_format($eventPriceMax, 0, ',', '.') : 'Gratis' }}
                            </span>
                        </div>
                        <input
                            type="range"
                            id="event-price-range"
                            class="price-range-input"
                            min="0"
                            max="{{ $eventPriceMax }}"
                            step="{{ $eventPriceStep }}"
                            value="{{ $eventPriceMax }}"
                            aria-label="Maksimum harga tiket"
                        >
                    </div>

                    <label class="free-filter-check">
                        <input type="checkbox" id="event-free-filter">
                        <span>Gratis</span>
                    </label>

                    <button type="button" id="event-filter-reset" class="filter-reset-btn">
                        Reset
                    </button>
                </div>

                <div class="search-result-count" id="event-search-count">
                    {{ $eventSearchItems->count() }} event tersedia
                </div>

                <div id="event-search-suggestions" class="search-suggestions" role="listbox" hidden></div>
            </div>

            {{-- Stats --}}
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="ticket" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Tiket Dimiliki</span>
                        <span class="stat-value">{{ $ticketCount }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="calendar" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Event Diikuti</span>
                        <span class="stat-value">{{ $eventCount }}</span>
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
                <div class="card" id="event-map">
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
                        <a href="#event-search" class="card-link">
                            Lihat semua
                            <x-icon name="arrow-right" size="14" />
                        </a>
                    </div>

                    <div
                        class="recommendation-list"
                        id="recommendation-list"
                    >
                        @include('user.partials.recommendations', ['recommendedEvents' => $recommendedEvents])
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
        const eventSearchItems = @json($eventSearchItems);
        const eventPriceMax = {{ $eventPriceMax }};

        const eventSearchInput = document.getElementById('event-search-input');
        const eventSearchClear = document.getElementById('event-search-clear');
        const eventSuggestions = document.getElementById('event-search-suggestions');
        const eventPriceRange = document.getElementById('event-price-range');
        const eventFreeFilter = document.getElementById('event-free-filter');
        const eventPriceRangeLabel = document.getElementById('event-price-range-label');
        const eventFilterReset = document.getElementById('event-filter-reset');
        const eventSearchCount = document.getElementById('event-search-count');
        let activeSuggestionIndex = -1;

        function formatCategoryName(category) {
            return (category || 'event')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, char => char.toUpperCase());
        }

        function formatRupiah(value) {
            const numericValue = Number(value);

            return numericValue > 0
                ? `Rp ${numericValue.toLocaleString('id-ID')}`
                : 'Gratis';
        }

        function hasActiveEventFilter() {
            return eventFreeFilter.checked || Number(eventPriceRange.value) < eventPriceMax;
        }

        function syncPriceRangeLabel() {
            eventPriceRange.disabled = eventFreeFilter.checked || eventPriceMax <= 0;
            eventPriceRangeLabel.textContent = eventFreeFilter.checked
                ? 'Gratis saja'
                : `Rp 0 - ${formatRupiah(eventPriceRange.value)}`;
        }

        function getFilteredSearchEvents() {
            const query = eventSearchInput.value.trim().toLowerCase();
            const priceLimit = Number(eventPriceRange.value);
            const onlyFree = eventFreeFilter.checked;

            return eventSearchItems.filter(event => {
                const eventPrice = Number(event.price);
                const searchableText = [
                    event.title,
                    event.location_name,
                    formatCategoryName(event.category),
                ].join(' ').toLowerCase();

                const matchesQuery = !query || searchableText.includes(query);
                const matchesPrice = onlyFree
                    ? eventPrice <= 0
                    : eventPrice <= priceLimit;

                return matchesQuery && matchesPrice;
            });
        }

        function updateSearchCount(count) {
            eventSearchCount.textContent = count === eventSearchItems.length
                ? `${count} event tersedia`
                : `${count} event cocok`;
        }

        function setActiveSuggestion(items, index) {
            items.forEach(item => item.classList.remove('active'));
            activeSuggestionIndex = index;

            if (items[activeSuggestionIndex]) {
                items[activeSuggestionIndex].classList.add('active');
            }
        }

        function appendSuggestion(event, index) {
            const link = document.createElement('a');
            link.href = event.url;
            link.className = 'search-suggestion';
            link.setAttribute('role', 'option');
            link.dataset.index = index;

            const title = document.createElement('span');
            title.className = 'suggestion-title';
            title.textContent = event.title;

            const status = document.createElement('span');
            status.className = `suggestion-status ${event.is_ended ? 'ended' : 'active'}`;
            status.textContent = event.display_status;

            const meta = document.createElement('span');
            meta.className = 'suggestion-meta';
            meta.textContent = `${formatCategoryName(event.category)} - ${event.location_name || '-'} - ${event.display_date}`;

            const price = document.createElement('span');
            price.className = 'suggestion-price';
            price.textContent = event.price_label;

            link.append(title, status, meta, price);
            eventSuggestions.appendChild(link);
        }

        function renderEventSuggestions(forceOpen = false) {
            const filteredEvents = getFilteredSearchEvents();
            const shouldOpen = forceOpen
                && (eventSearchInput.value.trim() || hasActiveEventFilter());

            updateSearchCount(filteredEvents.length);
            eventSuggestions.replaceChildren();
            activeSuggestionIndex = -1;

            if (!shouldOpen) {
                eventSuggestions.hidden = true;
                return;
            }

            if (!filteredEvents.length) {
                const empty = document.createElement('div');
                empty.className = 'search-suggestion-empty';
                empty.textContent = 'Event tidak ditemukan';
                eventSuggestions.appendChild(empty);
                eventSuggestions.hidden = false;
                return;
            }

            filteredEvents.slice(0, 8).forEach(appendSuggestion);
            eventSuggestions.hidden = false;
        }

        eventSearchInput.addEventListener('input', () => {
            eventSearchClear.hidden = !eventSearchInput.value.trim();
            renderEventSuggestions(true);
        });

        eventSearchInput.addEventListener('focus', () => {
            renderEventSuggestions(Boolean(eventSearchInput.value.trim() || hasActiveEventFilter()));
        });

        eventSearchInput.addEventListener('keydown', (event) => {
            const items = Array.from(eventSuggestions.querySelectorAll('.search-suggestion'));
            if (eventSuggestions.hidden || !items.length) return;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setActiveSuggestion(items, (activeSuggestionIndex + 1) % items.length);
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActiveSuggestion(items, activeSuggestionIndex <= 0 ? items.length - 1 : activeSuggestionIndex - 1);
            }

            if (event.key === 'Enter') {
                event.preventDefault();
                const target = items[activeSuggestionIndex] || items[0];
                window.location.href = target.href;
            }

            if (event.key === 'Escape') {
                eventSuggestions.hidden = true;
            }
        });

        eventPriceRange.addEventListener('input', () => {
            syncPriceRangeLabel();
            renderEventSuggestions(true);
        });

        eventFreeFilter.addEventListener('change', () => {
            syncPriceRangeLabel();
            renderEventSuggestions(true);
        });

        eventSearchClear.addEventListener('click', () => {
            eventSearchInput.value = '';
            eventSearchClear.hidden = true;
            renderEventSuggestions(hasActiveEventFilter());
            eventSearchInput.focus();
        });

        eventFilterReset.addEventListener('click', () => {
            eventSearchInput.value = '';
            eventPriceRange.value = eventPriceMax;
            eventFreeFilter.checked = false;
            eventSearchClear.hidden = true;
            syncPriceRangeLabel();
            renderEventSuggestions(false);
            eventSearchInput.focus();
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('#event-search')) {
                eventSuggestions.hidden = true;
            }
        });

        // ── Init map ────────────────────────────────────────────
        syncPriceRangeLabel();

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
                    })
                    .finally(() => {
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

    </script>

</body>
</html>
