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
                        <div class="empty-state">
                            <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
                            <p>Memuat rekomendasi...</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const savedLat = @json(session('user_location.lat'));
        const savedLng = @json(session('user_location.lng'));
        const eventSearchItems = @json($eventSearchItems);
        const eventPriceMax = {{ $eventPriceMax }};
        const recommendationList = document.getElementById('recommendation-list');

        const eventSearchInput = document.getElementById('event-search-input');
        const eventSearchClear = document.getElementById('event-search-clear');
        const eventSuggestions = document.getElementById('event-search-suggestions');
        const eventPriceRange = document.getElementById('event-price-range');
        const eventFreeFilter = document.getElementById('event-free-filter');
        const eventPriceRangeLabel = document.getElementById('event-price-range-label');
        const eventFilterReset = document.getElementById('event-filter-reset');
        const eventSearchCount = document.getElementById('event-search-count');
        const mapInfoText = document.getElementById('map-info-text');
        const radiusSelect = document.getElementById('radius-select');
        const mapCard = document.getElementById('event-map');
        const popupOptions = { autoClose: false, closeOnClick: false, maxWidth: 260 };

        let activeSuggestionIndex = -1;
        let userLat = toFiniteNumber(savedLat);
        let userLng = toFiniteNumber(savedLng);
        let userMarker = null;
        let radiusCircle = null;
        let dataMarkers = [];
        let currentMode = 'events';
        let focusedSearchMarker = null;
        let mapRequestSequence = 0;

        const eventMarkerById = new Map();
        const eventDistancesById = new Map();

        function toFiniteNumber(value) {
            const number = Number(value);

            return Number.isFinite(number) ? number : null;
        }

        function hasValidCoordinates(lat, lng) {
            return Number.isFinite(lat)
                && Number.isFinite(lng)
                && lat >= -90
                && lat <= 90
                && lng >= -180
                && lng <= 180;
        }

        function hasUserLocation() {
            return hasValidCoordinates(userLat, userLng);
        }

        function getEventLatLng(event) {
            const lat = toFiniteNumber(event?.lat);
            const lng = toFiniteNumber(event?.lng);

            return hasValidCoordinates(lat, lng) ? [lat, lng] : null;
        }

        function distanceBetweenMeters(fromLat, fromLng, toLat, toLng) {
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

        function normalizeDistance(value) {
            const distance = Number(value);

            return Number.isFinite(distance) ? distance : null;
        }

        function getEventDistanceMeters(event) {
            if (!event) return null;

            const directDistance = normalizeDistance(event.distance);
            if (directDistance !== null) return directDistance;

            const cachedDistance = eventDistancesById.get(Number(event.id));
            if (cachedDistance !== undefined) return cachedDistance;

            const coords = getEventLatLng(event);
            if (!coords || !hasUserLocation()) return null;

            const calculatedDistance = distanceBetweenMeters(userLat, userLng, coords[0], coords[1]);
            eventDistancesById.set(Number(event.id), calculatedDistance);

            return calculatedDistance;
        }

        function formatDistanceMeters(distance) {
            if (distance === null) return null;

            if (distance < 1000) {
                return `${Math.round(distance)} m`;
            }

            return `${(distance / 1000).toFixed(distance < 10000 ? 1 : 0)} km`;
        }

        function formatEventDistance(event) {
            const distance = formatDistanceMeters(getEventDistanceMeters(event));

            return distance ? `${distance} dari kamu` : 'Jarak belum tersedia';
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[char]));
        }

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

        function getRadius() {
            return parseInt(radiusSelect.value, 10);
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

        function findSearchEvent(eventId) {
            return eventSearchItems.find(event => Number(event.id) === Number(eventId));
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
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'search-suggestion';
            button.setAttribute('role', 'option');
            button.dataset.index = index;
            button.dataset.eventId = event.id;

            const title = document.createElement('span');
            title.className = 'suggestion-title';
            title.textContent = event.title;

            const status = document.createElement('span');
            status.className = `suggestion-status ${event.is_ended ? 'ended' : 'active'}`;
            status.textContent = event.display_status;

            const meta = document.createElement('span');
            meta.className = 'suggestion-meta';
            meta.textContent = `${formatCategoryName(event.category)} - ${event.location_name || '-'} - ${event.display_date}`;

            const footer = document.createElement('span');
            footer.className = 'suggestion-footer';

            const price = document.createElement('span');
            price.className = 'suggestion-price';
            price.textContent = event.price_label;

            const distance = document.createElement('span');
            distance.className = 'suggestion-distance';
            distance.textContent = formatEventDistance(event);

            footer.append(price, distance);
            button.append(title, status, meta, footer);
            button.addEventListener('click', () => focusEventFromSearch(event.id));
            eventSuggestions.appendChild(button);
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

        function buildNearbyUrl(baseUrl) {
            const params = new URLSearchParams({ radius: String(getRadius()) });

            if (hasUserLocation()) {
                params.set('lat', String(userLat));
                params.set('lng', String(userLng));
            }

            return `${baseUrl}?${params.toString()}`;
        }

        function buildEventPopup(event) {
            const priceLabel = event.price_label || formatRupiah(event.price);
            const distance = formatDistanceMeters(getEventDistanceMeters(event));
            const detailUrl = event.url || `/events/${event.id}`;

            return `<div class="popup-event">` +
                `<strong>${escapeHtml(event.title || 'Event')}</strong><br>` +
                `${escapeHtml(event.date || event.display_date || '-')}<br>` +
                `${escapeHtml(priceLabel)}` +
                (event.location_name ? `<br><small>${escapeHtml(event.location_name)}</small>` : '') +
                (distance ? `<br><small>${escapeHtml(distance)} dari kamu</small>` : '') +
                `<br><a href="${escapeHtml(detailUrl)}" class="popup-link">Lihat Detail</a>` +
            `</div>`;
        }

        function buildEOPopup(eo) {
            const ratingText = eo.review_count > 0
                ? `Rating ${Number(eo.average_rating).toFixed(1)} / 5 (${eo.review_count} ulasan)`
                : 'Belum ada ulasan';
            const distance = formatDistanceMeters(normalizeDistance(eo.distance));

            return `<div class="popup-event">` +
                `<strong>${escapeHtml(eo.name)}</strong><br>` +
                `${Number(eo.total_events)} event terdaftar<br>` +
                `${escapeHtml(ratingText)}<br>` +
                `Telepon: ${escapeHtml(eo.phone || '-')}` +
                (distance ? `<br><small>${escapeHtml(distance)} dari kamu</small>` : '') +
            `</div>`;
        }

        function openNearestEventPopups(events) {
            const nearest = events
                .map(event => ({
                    event,
                    marker: eventMarkerById.get(Number(event.id)),
                    distance: getEventDistanceMeters(event),
                }))
                .filter(item => item.marker && item.distance !== null)
                .sort((a, b) => a.distance - b.distance)
                .slice(0, 3);

            if (!nearest.length) return;

            nearest.forEach(item => item.marker.openPopup());

            const boundsItems = nearest.map(item => item.marker.getLatLng());
            if (userMarker) boundsItems.push(userMarker.getLatLng());

            map.fitBounds(L.latLngBounds(boundsItems), {
                padding: [40, 80],
                maxZoom: 13,
            });
        }

        function focusEventFromSearch(eventId) {
            const event = findSearchEvent(eventId);
            eventSuggestions.hidden = true;
            eventSearchInput.blur();

            if (!event) return;

            if (currentMode !== 'events' || !eventMarkerById.size) {
                setMapMode('events', { focusEventId: event.id, openNearest: false });
                return;
            }

            focusEventOnMap(event.id);
        }

        function focusEventOnMap(eventId) {
            const event = findSearchEvent(eventId);
            const marker = eventMarkerById.get(Number(eventId));
            const coords = marker ? marker.getLatLng() : getEventLatLng(event);

            if (!coords) {
                mapInfoText.textContent = 'Lokasi event belum tersedia di peta.';
                return;
            }

            const latlng = marker ? coords : L.latLng(coords[0], coords[1]);
            const targetZoom = Math.max(map.getZoom(), 14);
            let popupOpened = false;

            mapCard.scrollIntoView({ behavior: 'smooth', block: 'start' });

            const openPopup = () => {
                if (popupOpened) return;
                popupOpened = true;

                if (marker) {
                    if (focusedSearchMarker) {
                        map.removeLayer(focusedSearchMarker);
                        focusedSearchMarker = null;
                    }

                    marker.openPopup();
                    return;
                }

                if (event) {
                    openTemporaryEventMarker(event, latlng);
                }
            };

            map.once('moveend', openPopup);
            map.flyTo(latlng, targetZoom, { duration: 0.8 });
            window.setTimeout(openPopup, 900);
        }

        function openTemporaryEventMarker(event, latlng) {
            if (focusedSearchMarker) {
                map.removeLayer(focusedSearchMarker);
            }

            focusedSearchMarker = L.circleMarker(latlng, {
                radius: 10,
                fillColor: '#d8ff4f',
                color: '#ffffff',
                weight: 2.5,
                fillOpacity: 0.95,
            })
            .addTo(map)
            .bindPopup(buildEventPopup(event), popupOptions);

            focusedSearchMarker.openPopup();
        }

        function updateMapInfo(total, inRadius, label) {
            mapInfoText.textContent =
                `Menampilkan ${total} ${label}` +
                (inRadius !== null ? ` - ${inRadius} dalam radius ${getRadius() / 1000} km` : '');
        }

        function setMapMode(mode, options = {}) {
            currentMode = mode;
            document.querySelectorAll('.map-toggle-btn').forEach(button => {
                button.classList.toggle('active', button.dataset.mode === mode);
            });

            document.getElementById('map-title').textContent =
                currentMode === 'events' ? 'Event di Sekitarmu' : 'Event Organizer di Sekitarmu';

            return loadMapData(options);
        }

        function clearMapDataMarkers() {
            dataMarkers.forEach(marker => map.removeLayer(marker));
            dataMarkers = [];
            eventMarkerById.clear();

            if (focusedSearchMarker) {
                map.removeLayer(focusedSearchMarker);
                focusedSearchMarker = null;
            }
        }

        function loadMapData(options = {}) {
            const requestId = ++mapRequestSequence;
            const openNearest = options.openNearest ?? currentMode === 'events';
            const focusEventId = options.focusEventId ?? null;

            clearMapDataMarkers();

            const baseUrl = currentMode === 'events'
                ? `{{ route('events.nearby', [], false) }}`
                : `{{ route('eo.nearby', [], false) }}`;

            return fetch(buildNearbyUrl(baseUrl))
                .then(res => {
                    if (!res.ok) throw new Error('Gagal memuat data peta.');
                    return res.json();
                })
                .then(data => {
                    if (requestId !== mapRequestSequence) return null;

                    if (currentMode === 'events') {
                        const events = data.events || [];
                        renderEventMarkers(events, { openNearest: openNearest && !focusEventId });
                        updateMapInfo(data.count_total, data.count_in_radius, 'event');

                        if (focusEventId) {
                            focusEventOnMap(focusEventId);
                        }
                    } else {
                        renderEOMarkers(data.organizers || []);
                        updateMapInfo(data.count_total, data.count_in_radius, 'EO');
                    }

                    if (!eventSuggestions.hidden) {
                        renderEventSuggestions(true);
                    }

                    return data;
                })
                .catch(() => {
                    mapInfoText.textContent = 'Gagal memuat data.';
                });
        }

        function renderEventMarkers(events, options = {}) {
            events.forEach(event => {
                const coords = getEventLatLng(event);
                if (!coords) return;

                const distance = getEventDistanceMeters(event);
                if (distance !== null) {
                    eventDistancesById.set(Number(event.id), distance);
                }

                const inRadius = event.in_radius === true || event.in_radius === null;
                const marker = L.circleMarker(coords, {
                    radius: inRadius ? 9 : 6,
                    fillColor: inRadius ? '#ff5da2' : '#9ca3af',
                    color: inRadius ? '#ffffff' : '#d1d5db',
                    weight: inRadius ? 2.5 : 1.5,
                    fillOpacity: inRadius ? 0.95 : 0.6,
                    opacity: inRadius ? 1 : 0.7,
                })
                .addTo(map)
                .bindPopup(buildEventPopup(event), popupOptions);

                eventMarkerById.set(Number(event.id), marker);
                dataMarkers.push(marker);
            });

            if (options.openNearest) {
                openNearestEventPopups(events);
            }
        }

        function renderEOMarkers(organizers) {
            organizers.forEach(eo => {
                const lat = toFiniteNumber(eo.lat);
                const lng = toFiniteNumber(eo.lng);
                if (!hasValidCoordinates(lat, lng)) return;

                const inRadius = eo.in_radius === true || eo.in_radius === null;
                const marker = L.circleMarker([lat, lng], {
                    radius: inRadius ? 9 : 6,
                    fillColor: inRadius ? '#60a5fa' : '#9ca3af',
                    color: inRadius ? '#ffffff' : '#d1d5db',
                    weight: inRadius ? 2.5 : 1.5,
                    fillOpacity: inRadius ? 0.95 : 0.6,
                    opacity: inRadius ? 1 : 0.7,
                })
                .addTo(map)
                .bindPopup(buildEOPopup(eo), popupOptions);

                dataMarkers.push(marker);
            });
        }

        function placeUserOnMap(lat, lng) {
            userLat = toFiniteNumber(lat);
            userLng = toFiniteNumber(lng);
            eventDistancesById.clear();

            if (userMarker) map.removeLayer(userMarker);
            if (radiusCircle) map.removeLayer(radiusCircle);

            userMarker = L.circleMarker([userLat, userLng], {
                radius: 8,
                fillColor: '#d8ff4f',
                color: '#0f1117',
                weight: 2,
                fillOpacity: 1,
            }).addTo(map).bindPopup('Lokasi kamu');

            radiusCircle = L.circle([userLat, userLng], {
                radius: getRadius(),
                color: '#d8ff4f',
                fillColor: '#d8ff4f',
                fillOpacity: 0.06,
                weight: 1.5,
                dashArray: '6, 6',
            }).addTo(map);

            map.setView([userLat, userLng], 12);
            mapInfoText.textContent = `Memuat data dalam radius ${getRadius() / 1000} km...`;
            loadMapData();

            if (!eventSuggestions.hidden) {
                renderEventSuggestions(true);
            }
        }

        syncPriceRangeLabel();
        loadRecommendations();

        const defaultLat = -6.2088;
        const defaultLng = 106.8456;
        const initialLat = hasUserLocation() ? userLat : defaultLat;
        const initialLng = hasUserLocation() ? userLng : defaultLng;

        const map = L.map('map', { zoomControl: true }).setView(
            [initialLat, initialLng],
            hasUserLocation() ? 12 : 5
        );

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: 19,
        }).addTo(map);

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
                target.click();
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

        document.querySelectorAll('.map-toggle-btn').forEach(button => {
            button.addEventListener('click', () => setMapMode(button.dataset.mode));
        });

        radiusSelect.addEventListener('change', () => {
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
            }

            loadMapData();
        });

        if (hasUserLocation()) {
            placeUserOnMap(userLat, userLng);
            document.getElementById('location-status').textContent = 'Terdeteksi';
        } else {
            requestUserLocation();
        }

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
                () => {
                    userLat = null;
                    userLng = null;
                    eventDistancesById.clear();
                    document.getElementById('location-status').textContent = 'Ditolak';
                    mapInfoText.textContent = 'Izin lokasi ditolak. Aktifkan GPS untuk fitur ini.';
                    map.setView([defaultLat, defaultLng], 5);
                    loadMapData();
                    if (onDone) onDone();
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }

        const refreshBtn = document.getElementById('refresh-location-btn');
        const refreshIcon = document.getElementById('refresh-icon');

        refreshBtn.addEventListener('click', () => {
            refreshBtn.disabled = true;
            refreshIcon.classList.add('spinning');
            document.getElementById('location-status').textContent = 'Memperbarui...';
            mapInfoText.textContent = 'Mendeteksi lokasi kamu...';

            requestUserLocation(() => {
                refreshBtn.disabled = false;
                refreshIcon.classList.remove('spinning');
            });
        });

        function loadRecommendations() {
            fetch('{{ route("dashboard.recommendations", [], false) }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat rekomendasi.');

                return response.json();
            })
            .then(data => {
                recommendationList.innerHTML = data.html || '';
            })
            .catch(() => {
                recommendationList.innerHTML = `
                    <div class="empty-state">
                        <p>Rekomendasi belum bisa dimuat.</p>
                    </div>
                `;
            });
        }
    </script>

</body>
</html>
