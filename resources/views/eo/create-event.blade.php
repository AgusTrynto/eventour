<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Event - EvenTour</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    @vite(['resources/css/eo/dashboard.css', 'resources/css/eo/create-event.css', 'resources/js/app.js'])
</head>

<body class="eo-dashboard-page">

    <div class="bg-glow"></div>

    {{-- NAVBAR --}}
    <header class="navbar">
        <div class="container-custom">
            <a href="/" class="logo">Even<span>Tour</span></a>

            <nav class="nav-links">
                <a href="{{ route('eo.dashboard') }}" class="nav-link">Dashboard EO</a>
                <a href="{{ route('eo.events.create') }}" class="nav-link active">+ Tambah Event</a>
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
        <div class="container-custom narrow">

            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">EVENT BARU</span>
                    <h1>Ajukan Event</h1>
                    <p>Lengkapi detail event. Event akan tampil di map setelah disetujui admin.</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('eo.events.store') }}" method="POST" class="card event-form">
                @csrf

                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Event</label>
                        <input type="text" name="title" placeholder="Contoh: Java Jazz Festival 2026"
                            value="{{ old('title') }}" required>
                    </div>

                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category">
                            <option value="">Pilih kategori</option>
                            <option value="musik" {{ old('category') === 'musik' ? 'selected' : '' }}>Musik</option>
                            <option value="seni" {{ old('category') === 'seni' ? 'selected' : '' }}>Seni & Budaya</option>
                            <option value="olahraga" {{ old('category') === 'olahraga' ? 'selected' : '' }}>Olahraga</option>
                            <option value="kuliner" {{ old('category') === 'kuliner' ? 'selected' : '' }}>Kuliner</option>
                            <option value="teknologi" {{ old('category') === 'teknologi' ? 'selected' : '' }}>Teknologi</option>
                            <option value="lainnya" {{ old('category') === 'lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="4" placeholder="Ceritakan tentang eventmu...">{{ old('description') }}</textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal & Waktu Mulai</label>
                        <input type="datetime-local" name="start_date" value="{{ old('start_date') }}" required>
                    </div>

                    <div class="form-group">
                        <label>Tanggal & Waktu Selesai (opsional)</label>
                        <input type="datetime-local" name="end_date" value="{{ old('end_date') }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Harga Tiket (Rp)</label>
                        <input type="number" name="price" min="0" step="1000" placeholder="0 untuk gratis"
                            value="{{ old('price', 0) }}" required>
                    </div>

                    <div class="form-group">
                        <label>Kuota (opsional)</label>
                        <input type="number" name="quota" min="1" placeholder="Tanpa batas jika kosong"
                            value="{{ old('quota') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Nama Lokasi</label>
                    <input type="text" name="location_name" placeholder="Contoh: GBK Senayan, Jakarta"
                        value="{{ old('location_name') }}" required>
                </div>

                {{-- MAP PICKER --}}
                <div class="form-group">
                    <label>Titik Lokasi di Map <span class="hint">(klik untuk menandai)</span></label>
                    <div id="map-picker" class="map-picker"></div>
                    <div class="coord-display">
                        <span id="coord-text">Belum ada titik dipilih</span>
                    </div>
                </div>

                <input type="hidden" name="lat" id="lat-input" value="{{ old('lat') }}" required>
                <input type="hidden" name="lng" id="lng-input" value="{{ old('lng') }}" required>

                <div class="form-actions">
                    <a href="{{ route('eo.dashboard') }}" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-submit">Ajukan Event</button>
                </div>
            </form>

        </div>
    </main>

    <footer>© 2026 EvenTour. All Rights Reserved.</footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const defaultLat = -6.2088;
        const defaultLng = 106.8456;

        const map = L.map('map-picker').setView([defaultLat, defaultLng], 5);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: 19,
        }).addTo(map);

        let marker = null;
        const latInput  = document.getElementById('lat-input');
        const lngInput  = document.getElementById('lng-input');
        const coordText = document.getElementById('coord-text');

        // Kalau ada old() value (form gagal validasi), tampilkan marker lagi
        const oldLat = "{{ old('lat') }}";
        const oldLng = "{{ old('lng') }}";

        if (oldLat && oldLng) {
            placeMarker(parseFloat(oldLat), parseFloat(oldLng));
            map.setView([oldLat, oldLng], 13);
        }

        map.on('click', (e) => {
            placeMarker(e.latlng.lat, e.latlng.lng);
        });

        function placeMarker(lat, lng) {
            if (marker) map.removeLayer(marker);

            marker = L.marker([lat, lng], { draggable: true }).addTo(map);

            marker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                updateCoords(pos.lat, pos.lng);
            });

            updateCoords(lat, lng);
        }

        function updateCoords(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            coordText.textContent = `📍 ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        }
    </script>

</body>
</html>