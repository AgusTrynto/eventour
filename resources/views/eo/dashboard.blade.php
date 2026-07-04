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

    {{-- NAVBAR (desktop) --}}
    <header class="navbar">
        <div class="container-custom">
            <button type="button" class="hamburger-btn" id="hamburger-btn" aria-label="Buka menu">
                <span></span><span></span><span></span>
            </button>

            <a href="/dashboard" class="logo">Even<span>Tour</span></a>

            <nav class="nav-links">
                <a href="{{ route('eo.dashboard') }}" class="nav-link active">Dashboard EO</a>
                <a href="{{ route('eo.events.create') }}" class="nav-link">
                    <x-icon name="circle-plus" :size="15" />
                    Tambah Event
                </a>
                <a href="{{ route('eo.scan') }}" class="nav-link">Scan Tiket</a>
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

    {{-- SIDEBAR (mobile) --}}
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <aside class="mobile-sidebar" id="mobile-sidebar">
        <div class="sidebar-top">
            <a href="/dashboard" class="logo">Even<span>Tour</span></a>
            <button type="button" class="sidebar-close" id="sidebar-close" aria-label="Tutup menu">
                <x-icon name="x" :size="16" />
            </button>
        </div>

        <div class="sidebar-user">
            <span class="user-name">{{ $organizer->org_name }}</span>
            <span class="role-badge">EO</span>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('eo.dashboard') }}" class="sidebar-link active">
                <x-icon name="bar-chart" :size="18" />
                Dashboard EO
            </a>
            <a href="{{ route('eo.events.create') }}" class="sidebar-link">
                <x-icon name="circle-plus" :size="18" />
                Tambah Event
            </a>
            <a href="{{ route('eo.scan') }}" class="sidebar-link">
                <x-icon name="camera" :size="18" />
                Scan Tiket
            </a>
        </nav>

        <form action="{{ route('logout') }}" method="POST" class="sidebar-logout">
            @csrf
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </aside>

    <main class="main-content">
        <div class="container-custom">

            @if (session('success'))
                <div class="alert alert-success">
                    <x-icon name="check-circle" :size="18" />
                    {{ session('success') }}
                </div>
            @endif

            {{-- Welcome --}}
            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">DASHBOARD EVENT ORGANIZER</span>
                    <h1>{{ $organizer->org_name }}</h1>
                    <p>Kelola event yang sudah disetujui dan ajukan event baru.</p>
                </div>

                <a href="{{ route('eo.events.create') }}" class="btn-add-event">
                    <x-icon name="circle-plus" :size="18" />
                    Tambah Event
                </a>
            </div>

            {{-- Stats --}}
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="check-circle" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Event Disetujui</span>
                        <span class="stat-value">{{ $approvedEvents->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="clock" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Menunggu Persetujuan</span>
                        <span class="stat-value">{{ $pendingEvents->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="x-circle" :size="28" /></div>
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
                            <span class="empty-state-icon"><x-icon name="inbox" :size="38" /></span>
                            <p>Tidak ada event yang menunggu persetujuan.</p>
                        </div>
                    @else
                        <div class="event-list">
                            @foreach ($pendingEvents as $event)
                                <div class="event-item">
                                    <div class="event-icon pending"><x-icon name="clock" :size="20" /></div>
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
                        <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
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
                            <span>Ulasan</span>
                        </div>

                        @foreach ($approvedEvents as $event)
                            <div class="event-table-row">
                                <span class="event-name">{{ $event->title }}</span>
                                <span>{{ $event->start_date->translatedFormat('d M Y, H:i') }}</span>
                                <span>{{ $event->location_name }}</span>
                                <span>{{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}</span>
                                <span>{{ $event->quota ?? 'Tanpa batas' }}</span>
                                <span>
                                    <a href="{{ route('eo.events.reviews', $event) }}" class="btn-reviews">Lihat</a>
                                </span>
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
                                <div class="event-icon rejected"><x-icon name="x-circle" :size="20" /></div>
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

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

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
