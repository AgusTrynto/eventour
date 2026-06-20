<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard EO - EvenTour</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @vite(['resources/css/eo/dashboard.css', 'resources/js/app.js'])
</head>

<body class="eo-dashboard-page">

    <div class="bg-glow"></div>

    {{-- NAVBAR --}}
    <header class="navbar">
        <div class="container-custom">
            <a href="/dashboard" class="logo">Even<span>Tour</span></a>

            <nav class="nav-links">
                <a href="{{ route('eo.dashboard') }}" class="nav-link active">Dashboard EO</a>
                <a href="{{ route('eo.events.create') }}" class="nav-link">+ Tambah Event</a>
            </nav>

            <div class="nav-right">
                <span class="user-name">{{ $organizer->org_name }}</span>
                <span class="role-badge">EO</span>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container-custom">

            @if (session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif

            {{-- Welcome --}}
            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">DASHBOARD EVENT ORGANIZER</span>
                    <h1>{{ $organizer->org_name }} 🎉</h1>
                    <p>Kelola event yang sudah disetujui dan ajukan event baru.</p>
                </div>

                <a href="{{ route('eo.events.create') }}" class="btn-add-event">
                    + Tambah Event
                </a>
            </div>

            {{-- Stats --}}
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <span class="stat-label">Event Disetujui</span>
                        <span class="stat-value">{{ $approvedEvents->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <span class="stat-label">Menunggu Persetujuan</span>
                        <span class="stat-value">{{ $pendingEvents->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-info">
                        <span class="stat-label">Ditolak</span>
                        <span class="stat-value">{{ $rejectedEvents->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="content-grid">

                {{-- MAP: event yang sudah approved --}}
                <div class="card">
                    <div class="card-header">
                        <h2>Lokasi Event Disetujui</h2>
                        <span class="card-subtitle">{{ $approvedEvents->count() }} event tampil di map</span>
                    </div>
                    <div id="map" class="map-container"></div>
                </div>

                {{-- PENDING EVENTS --}}
                <div class="card">
                    <div class="card-header">
                        <h2>Menunggu Persetujuan</h2>
                    </div>

                    @if ($pendingEvents->isEmpty())
                        <div class="empty-state">
                            <span>📭</span>
                            <p>Tidak ada event yang menunggu persetujuan.</p>
                        </div>
                    @else
                        <div class="event-list">
                            @foreach ($pendingEvents as $event)
                                <div class="event-item">
                                    <div class="event-icon pending">⏳</div>
                                    <div class="event-info">
                                        <span class="event-title">{{ $event->title }}</span>
                                        <span class="event-meta">
                                            {{ $event->location_name }} ·
                                            {{ $event->start_date->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                    <span class="status-badge status-pending">Pending</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- APPROVED EVENTS LIST --}}
            <div class="card full-width">
                <div class="card-header">
                    <h2>Event Disetujui ({{ $approvedEvents->count() }})</h2>
                </div>

                @if ($approvedEvents->isEmpty())
                    <div class="empty-state">
                        <span>🎪</span>
                        <p>Belum ada event yang disetujui dan tampil di map.</p>
                        <a href="{{ route('eo.events.create') }}" class="btn-explore">Tambah Event Pertama</a>
                    </div>
                @else
                    <div class="event-table">
                        <div class="event-table-header">
                            <span>Nama Event</span>
                            <span>Tanggal</span>
                            <span>Lokasi</span>
                            <span>Harga</span>
                            <span>Kuota</span>
                        </div>

                        @foreach ($approvedEvents as $event)
                            <div class="event-table-row">
                                <span class="event-name">{{ $event->title }}</span>
                                <span>{{ $event->start_date->translatedFormat('d M Y, H:i') }}</span>
                                <span>{{ $event->location_name }}</span>
                                <span>{{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}</span>
                                <span>{{ $event->quota ?? '∞' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- REJECTED EVENTS (kalau ada) --}}
            @if ($rejectedEvents->isNotEmpty())
                <div class="card full-width">
                    <div class="card-header">
                        <h2>Event Ditolak ({{ $rejectedEvents->count() }})</h2>
                    </div>

                    <div class="event-list">
                        @foreach ($rejectedEvents as $event)
                            <div class="event-item">
                                <div class="event-icon rejected">❌</div>
                                <div class="event-info">
                                    <span class="event-title">{{ $event->title }}</span>
                                    <span class="event-meta">
                                        {{ $event->location_name }} ·
                                        {{ $event->start_date->translatedFormat('d M Y') }}
                                    </span>
                                    @if ($event->reject_reason)
                                        <span class="event-reason">Alasan: {{ $event->reject_reason }}</span>
                                    @endif
                                </div>
                                <span class="status-badge status-rejected">Ditolak</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </main>

    <footer>© 2026 EvenTour. All Rights Reserved.</footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        @php
            $mapEvents = $approvedEvents->map(function ($e) {
                return [
                    'title' => $e->title,
                    'lat'   => $e->lat,
                    'lng'   => $e->lng,
                    'date'  => $e->start_date->translatedFormat('d M Y'),
                ];
            });
        @endphp

        const events = @json($mapEvents);

        const defaultLat = -6.2088;
        const defaultLng = 106.8456;

        const map = L.map('map').setView([defaultLat, defaultLng], 5);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: 19,
        }).addTo(map);

        if (events.length > 0) {
            const bounds = [];

            events.forEach(ev => {
                const marker = L.circleMarker([ev.lat, ev.lng], {
                    radius: 8,
                    fillColor: '#d8ff4f',
                    color: '#0f1117',
                    weight: 2,
                    fillOpacity: 1,
                }).addTo(map).bindPopup(`<strong>${ev.title}</strong><br>${ev.date}`);

                bounds.push([ev.lat, ev.lng]);
            });

            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
        }
    </script>

</body>
</html>