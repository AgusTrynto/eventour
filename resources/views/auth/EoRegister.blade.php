<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar EO - EvenTour</title>

    @vite(['resources/css/auth/register.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>

<body class="register-page">

    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">
            Even<span>Tour</span>
        </a>

        <a href="/login" class="login-link">
            Sudah punya akun?
        </a>
    </header>

    <main class="main-content">
        <div class="register-card">

            <div class="heading">
                <span class="badge">JADI MITRA EVENTOUR</span>

                <h1>
                    Daftar Sebagai EO
                </h1>

                <p>
                    Buat dan kelola eventmu sendiri, jangkau ribuan pengunjung melalui EvenTour.
                </p>
            </div>

            {{-- Flash error --}}
            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <form action="{{ route('eo.register.submit') }}" method="POST" class="register-form">
                @csrf

                <div class="form-group">
                    <label>Nama Organisasi / EO</label>
                    <input type="text" name="org_name" placeholder="Contoh: Java Production"
                        value="{{ old('org_name') }}" required>
                    @error('org_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Nama Penanggung Jawab</label>
                    <input type="text" name="name" placeholder="Masukkan nama lengkap" value="{{ old('name') }}"
                        required>
                    @error('name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="nama@email.com" value="{{ old('email') }}" required>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="text" name="phone" placeholder="08xxxxxxxxxx" value="{{ old('phone') }}" required>
                    @error('phone')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Alamat (opsional)</label>
                    <input type="text" name="address" placeholder="Alamat organisasi/EO" value="{{ old('address') }}">
                    @error('address')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>


                <div class="form-group">
                    <label>Lokasi Kantor/Basis Operasi <span class="hint">(klik untuk menandai)</span></label>
                    <div id="map-picker" class="map-picker-small"></div>
                    <div class="coord-display">
                        <span id="coord-text">Belum ada titik dipilih</span>
                    </div>
                </div>

                <input type="hidden" name="lat" id="lat-input" value="{{ old('lat') }}" required>
                <input type="hidden" name="lng" id="lng-input" value="{{ old('lng') }}" required>


                <div class="form-group">
                    <label>Nama Bank</label>
                    <select name="bank_name" required>
                        <option value="">Pilih bank</option>
                        <option value="BCA" {{ old('bank_name') === 'BCA' ? 'selected' : '' }}>BCA</option>
                        <option value="BNI" {{ old('bank_name') === 'BNI' ? 'selected' : '' }}>BNI</option>
                        <option value="BRI" {{ old('bank_name') === 'BRI' ? 'selected' : '' }}>BRI</option>
                        <option value="Mandiri" {{ old('bank_name') === 'Mandiri' ? 'selected' : '' }}>Mandiri</option>
                        <option value="CIMB Niaga" {{ old('bank_name') === 'CIMB Niaga' ? 'selected' : '' }}>CIMB Niaga
                        </option>
                        <option value="Lainnya" {{ old('bank_name') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('bank_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Nomor Rekening</label>
                    <input type="text" name="bank_account_number" placeholder="Contoh: 1234567890"
                        value="{{ old('bank_account_number') }}" required>
                    @error('bank_account_number')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Nama Pemilik Rekening</label>
                    <input type="text" name="bank_account_name" placeholder="Sesuai buku tabungan"
                        value="{{ old('bank_account_name') }}" required>
                    @error('bank_account_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="eo-password" name="password" placeholder="Minimal 8 karakter" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('eo-password', this)" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="eo-password_confirmation" name="password_confirmation" placeholder="Ulangi password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('eo-password_confirmation', this)" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <span id="eo-password-match-status" class="password-hint">Ketik ulang password untuk konfirmasi</span>
                </div>

                <div class="checkbox">
                    <input type="checkbox" name="agree" required>

                    <span>
                        Saya menyetujui
                        <a href="#">Syarat & Ketentuan</a>
                        dan
                        <a href="#">Kebijakan Privasi</a>
                        sebagai Event Organizer
                    </span>
                </div>

                <button type="submit" class="btn-register">
                    Daftar Sebagai EO
                </button>
            </form>

            <div class="divider"></div>

            <p class="eo-link">
                Ingin mendaftar sebagai peserta biasa?
                <a href="/register">Daftar Akun Biasa</a>
            </p>

        </div>
    </main>

    <footer>
        Copyright 2026 EvenTour. All Rights Reserved.
    </footer>


    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const defaultLat = -6.2088, defaultLng = 106.8456;
        const map = L.map('map-picker').setView([defaultLat, defaultLng], 5);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO', maxZoom: 19,
        }).addTo(map);

        let marker = null;
        const latInput = document.getElementById('lat-input');
        const lngInput = document.getElementById('lng-input');
        const coordText = document.getElementById('coord-text');

        map.on('click', (e) => {
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng, { draggable: true }).addTo(map);
            updateCoords(e.latlng.lat, e.latlng.lng);

            marker.on('dragend', (ev) => {
                const pos = ev.target.getLatLng();
                updateCoords(pos.lat, pos.lng);
            });
        });

        function updateCoords(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            coordText.textContent = `Koordinat: ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        }
    </script>

</body>

</html>
