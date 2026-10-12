<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Tiket - EvenTour</title>
    @vite(['resources/css/eo/dashboard.css', 'resources/css/eo/scan.css', 'resources/js/app.js'])
</head>

<body class="eo-dashboard-page">

    <div class="bg-glow"></div>

    @include('eo.partials.navbar', ['active' => 'scan'])

    <main class="main-content">
        <div class="container-custom narrow">

            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">CHECK-IN PESERTA</span>
                    <h1>Scan Tiket</h1>
                    <p>Arahkan kamera ke QR code tiket peserta untuk validasi masuk.</p>
                </div>
            </div>

            <div class="card">
                <div class="form-group">
                    <label>Pilih Event</label>
                    <select id="event-select">
                        <option value="">- Pilih event yang sedang berlangsung -</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" {{ count($events) === 1 ? 'selected' : '' }}>{{ $event->title }} - {{ $event->start_date?->translatedFormat('d M Y') }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="scanner-wrapper" class="scanner-wrapper" style="display:none;">
                    <div id="qr-reader"></div>
                </div>

                <div id="scan-result" class="scan-result" style="display:none;"></div>

                <div id="scan-placeholder" class="scan-placeholder">
                    <span class="empty-state-icon"><x-icon name="camera" :size="40" /></span>
                    <p>Pilih event terlebih dahulu untuk mulai scan.</p>
                </div>
            </div>

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        let html5QrCode = null;
        let currentEventId = null;
        let isProcessing = false;
        let isStarting = false;

        const eventSelect     = document.getElementById('event-select');
        const scannerWrapper  = document.getElementById('scanner-wrapper');
        const scanPlaceholder = document.getElementById('scan-placeholder');
        const scanResult      = document.getElementById('scan-result');

        eventSelect.addEventListener('change', async () => {
            currentEventId = eventSelect.value;
            scanResult.style.display = 'none';

            if (!currentEventId) {
                await stopScanner();
                scannerWrapper.style.display = 'none';
                scanPlaceholder.style.display = 'flex';
                return;
            }

            scanPlaceholder.style.display = 'none';
            await startScanner();
        });

        async function startScanner() {
            if (!currentEventId || isStarting) return;
            isStarting = true;

            scanResult.style.display = 'none';
            scanPlaceholder.style.display = 'none';
            scannerWrapper.style.display = 'block';

            await stopScanner();

            try {
                html5QrCode = new Html5Qrcode("qr-reader");
                await html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    onScanSuccess,
                    () => {} // abaikan kegagalan deteksi frame biasa
                );
                isProcessing = false;
            } catch (err) {
                console.error('Kamera error:', err);
                scannerWrapper.style.display = 'none';
                showCameraError('Tidak bisa mengakses kamera: ' + (err.message || err));
            } finally {
                isStarting = false;
            }
        }

        async function stopScanner() {
            if (!html5QrCode) return;

            const instance = html5QrCode;
            html5QrCode = null;

            try {
                const isScanning = typeof instance.isScanning === 'boolean' ? instance.isScanning : true;
                if (isScanning) {
                    await instance.stop();
                }
            } catch (err) {
                console.warn('stopScanner note:', err);
            }

            try {
                instance.clear();
            } catch (err) {}

            const qrContainer = document.getElementById('qr-reader');
            if (qrContainer) {
                qrContainer.innerHTML = '';
            }
        }

        async function onScanSuccess(decodedText) {
            if (isProcessing) return;
            isProcessing = true;

            // 1. Kamera langsung ditutup dan disembunyikan
            scannerWrapper.style.display = 'none';
            await stopScanner();

            // 2. Tampilkan loading validasi
            showLoading('Memvalidasi tiket...');

            // 3. Validasi tiket ke backend
            try {
                const res = await fetch('{{ route("eo.scan.validate", [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        ticket_code: decodedText,
                        event_id: currentEventId,
                    }),
                });

                const data = await res.json().catch(() => ({}));
                showResult(res.ok && data.valid, data.message || (res.ok ? 'Tiket valid! Selamat datang.' : 'Tiket tidak valid.'), data.ticket);
            } catch (err) {
                console.error('Scan fetch error:', err);
                showResult(false, 'Gagal menghubungi server. Periksa koneksi internet Anda.', null);
            }
        }

        function showLoading(text = 'Memverifikasi tiket...') {
            scanResult.style.display = 'block';
            scanResult.className = 'scan-result scan-result-loading';
            scanResult.innerHTML = `
                <div class="scan-loading">
                    <div class="scan-spinner"></div>
                    <p>${escapeHtml(text)}</p>
                </div>
            `;
        }

        function showResult(valid, message, ticket) {
            scanResult.style.display = 'block';
            scanResult.className = 'scan-result ' + (valid ? 'result-valid' : 'result-invalid');

            const badgeText = valid ? 'Check-in Berhasil' : 'Check-in Gagal';
            const badgeClass = valid ? 'badge-valid' : 'badge-invalid';

            let detailsHtml = '';
            if (ticket) {
                detailsHtml = `
                    <div class="result-details">
                        ${ticket.holder ? `
                            <div class="result-detail-row">
                                <span class="detail-label">Nama Peserta</span>
                                <strong class="detail-value">${escapeHtml(ticket.holder)}</strong>
                            </div>
                        ` : ''}
                        ${ticket.nik ? `
                            <div class="result-detail-row">
                                <span class="detail-label">NIK</span>
                                <span class="detail-value">${escapeHtml(ticket.nik)}</span>
                            </div>
                        ` : ''}
                        ${ticket.code ? `
                            <div class="result-detail-row">
                                <span class="detail-label">Kode Tiket</span>
                                <code class="detail-code">${escapeHtml(ticket.code)}</code>
                            </div>
                        ` : ''}
                        ${ticket.event ? `
                            <div class="result-detail-row">
                                <span class="detail-label">Event</span>
                                <span class="detail-value">${escapeHtml(ticket.event)}</span>
                            </div>
                        ` : ''}
                    </div>
                `;
            }

            scanResult.innerHTML = `
                <span class="result-badge ${badgeClass}">${badgeText}</span>
                <div class="result-icon">${resultIcon(valid)}</div>
                <p class="result-message">${escapeHtml(message)}</p>
                ${detailsHtml}
                <div class="result-actions">
                    <button type="button" id="btn-scan-again" class="btn-scan-again">
                        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="4"></circle>
                        </svg>
                        <span>Scan Lagi</span>
                    </button>
                </div>
            `;

            const scanAgainBtn = document.getElementById('btn-scan-again');
            scanAgainBtn?.addEventListener('click', () => {
                scanResult.style.display = 'none';
                startScanner();
            });

            setTimeout(() => {
                scanAgainBtn?.focus();
            }, 50);
        }

        function showCameraError(message) {
            scanResult.style.display = 'block';
            scanResult.className = 'scan-result result-invalid';
            scanResult.innerHTML = `
                <span class="result-badge badge-invalid">Kamera Tidak Aktif</span>
                <div class="result-icon">${resultIcon(false)}</div>
                <p class="result-message">${escapeHtml(message)}</p>
                <div class="result-actions">
                    <button type="button" id="btn-retry-camera" class="btn-scan-again">
                        <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                        </svg>
                        <span>Coba Buka Kamera Lagi</span>
                    </button>
                </div>
            `;

            document.getElementById('btn-retry-camera')?.addEventListener('click', () => {
                startScanner();
            });
        }

        function resultIcon(valid) {
            const path = valid
                ? '<circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path>'
                : '<circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6"></path><path d="m9 9 6 6"></path>';

            return `
                <svg class="icon" width="48" height="48" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">${path}</svg>
            `;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Jalankan scanner otomatis jika event sudah dipilih saat dimuat
        if (eventSelect.value) {
            currentEventId = eventSelect.value;
            startScanner();
        }

        window.addEventListener('beforeunload', () => {
            stopScanner();
        });
    </script>

</body>
</html>
