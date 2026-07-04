<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $event->title }} - EvenTour</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @vite(['resources/css/event/show.css', 'resources/js/app.js'])
</head>

<body class="event-detail-page">

    <div class="bg-glow"></div>

    {{-- NAVBAR --}}
    <header class="navbar">
        <div class="container-custom">
            <a href="/" class="logo">Even<span>Tour</span></a>
            <a href="{{ url()->previous('/dashboard') }}" class="back-link">
                <x-icon name="arrow-left" :size="16" />
                Kembali
            </a>
        </div>
    </header>

    <main class="main-content">
        <div class="container-custom narrow">

            {{-- HEADER EVENT --}}
            <div class="event-header">
                <span class="category-badge">{{ $event->category ?? 'Event' }}</span>
                <h1>{{ $event->title }}</h1>

                <div class="event-meta-row">
                    <div class="meta-item">
                        <span class="meta-icon"><x-icon name="calendar" :size="22" /></span>
                        <div>
                            <span class="meta-label">Tanggal</span>
                            <span class="meta-value">
                                {{ $event->start_date?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                                @if ($event->end_date)
                                    <br><span class="meta-sub">s/d {{ $event->end_date->translatedFormat('d F Y, H:i') }} WIB</span>
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
                            @if ($event->quota !== null && ($event->quota - $event->tickets_sold) <= 0)
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

        const map = L.map('map', {
            zoomControl: true,
            scrollWheelZoom: false,
        }).setView([eventLat, eventLng], 15);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: 19,
        }).addTo(map);

        L.circleMarker([eventLat, eventLng], {
            radius: 10,
            fillColor: '#ff5da2',
            color: '#ffffff',
            weight: 2.5,
            fillOpacity: 0.95,
        }).addTo(map).bindPopup(`<strong>{{ addslashes($event->title) }}</strong>`).openPopup();
    </script>

</body>
</html>
