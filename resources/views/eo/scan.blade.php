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

    <header class="navbar">
        <div class="container-custom">
            <a href="/" class="logo">Even<span>Tour</span></a>
            <nav class="nav-links">
                <a href="{{ route('eo.dashboard') }}" class="nav-link">Dashboard EO</a>
                <a href="{{ route('eo.scan') }}" class="nav-link active">Scan Tiket</a>
            </nav>
            <div class="nav-right">
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
                            <option value="{{ $event->id }}">{{ $event->title }} - {{ $event->start_date?->translatedFormat('d M Y') }}</option>
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

        const eventSelect      = document.getElementById('event-select');
        const scannerWrapper   = document.getElementById('scanner-wrapper');
        const scanPlaceholder  = document.getElementById('scan-placeholder');
        const scanResult       = document.getElementById('scan-result');

        eventSelect.addEventListener('change', () => {
            currentEventId = eventSelect.value;

            if (!currentEventId) {
                stopScanner();
                scannerWrapper.style.display = 'none';
                scanPlaceholder.style.display = 'flex';
                return;
            }

            scanPlaceholder.style.display = 'none';
            scannerWrapper.style.display = 'block';
            startScanner();
        });

        function startScanner() {
            html5QrCode = new Html5Qrcode("qr-reader");

            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                () => {} // ignore scan failure noise
            ).catch(err => {
                showResult(false, 'Tidak bisa mengakses kamera: ' + err);
            });
        }

        function stopScanner() {
            if (html5QrCode) {
                html5QrCode.stop().catch(() => {});
                html5QrCode = null;
            }
        }

        function onScanSuccess(decodedText) {
            if (isProcessing) return;
            isProcessing = true;

            fetch('{{ route("eo.scan.validate", [], false) }}', {
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
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                showResult(ok && data.valid, data.message, data.ticket);
            })
            .catch((err) => {
                console.error('Scan fetch error:', err);
                showResult(false, 'Gagal menghubungi server.');
            })
            .finally(() => {
                // Beri jeda sebelum bisa scan lagi
                setTimeout(() => { isProcessing = false; }, 2000);
            });
        }

        function showResult(valid, message, ticket) {
            scanResult.style.display = 'block';
            scanResult.className = 'scan-result ' + (valid ? 'result-valid' : 'result-invalid');

            scanResult.innerHTML = `
                <div class="result-icon">${resultIcon(valid)}</div>
                <p class="result-message">${message}</p>
                ${ticket ? `
                    <div class="result-details">
                        <div><strong>${ticket.holder}</strong></div>
                        <div>${ticket.code}</div>
                    </div>
                ` : ''}
            `;

            setTimeout(() => {
                scanResult.style.display = 'none';
            }, 3000);
        }

        function resultIcon(valid) {
            const path = valid
                ? '<path d="M9 12l2 2 4-4"></path><circle cx="12" cy="12" r="10"></circle>'
                : '<circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6"></path><path d="m9 9 6 6"></path>';

            return `
                <svg class="icon" width="40" height="40" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    aria-hidden="true">${path}</svg>
            `;
        }
    </script>

</body>
</html>
