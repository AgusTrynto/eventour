<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket - {{ $ticket->event->title }} - EvenTour</title>
    @vite(['resources/css/tickets/tickets.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="ticket-detail-page">

    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'tickets'])

    <main class="main-content">
        <div class="ticket-card">

            <div class="ticket-status status-{{ $ticket->status }}">
                @if ($ticket->status === 'valid')
                    <x-icon name="ticket" :size="16" />
                    Tiket Aktif
                @elseif ($ticket->status === 'used')
                    <x-icon name="check-circle" :size="16" />
                    Sudah Digunakan
                @else
                    <x-icon name="x-circle" :size="16" />
                    Dibatalkan
                @endif
            </div>

            <div class="qr-wrapper">
                <div id="qrcode"></div>
            </div>

            <p class="ticket-code">{{ $ticket->ticket_code }}</p>

            <div class="ticket-divider">
                <span></span><span></span>
            </div>

            <div class="ticket-info">
                <h2>{{ $ticket->event->title }}</h2>

                <div class="info-row">
                    <span><x-icon name="calendar" :size="16" /> Tanggal</span>
                    <span>{{ $ticket->event->start_date?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</span>
                </div>
                <div class="info-row">
                    <span><x-icon name="map-pin" :size="16" /> Lokasi</span>
                    <span>{{ $ticket->event->location_name }}</span>
                </div>
                <div class="info-row">
                    <span><x-icon name="user" :size="16" /> Atas Nama</span>
                    <span>{{ $ticket->user->name }}</span>
                </div>

                @if ($ticket->status === 'used')
                    <div class="info-row checked-in">
                        <span><x-icon name="check-circle" :size="16" /> Check-in</span>
                        <span>{{ $ticket->checked_in_at->translatedFormat('d M Y, H:i') }}</span>
                    </div>
                @endif
            </div>

            <p class="ticket-note">
                Tunjukkan QR code ini ke panitia saat masuk ke lokasi event.
            </p>

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById('qrcode'), {
            text: "{{ $ticket->ticket_code }}",
            width: 220,
            height: 220,
            colorDark: "#0f1117",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H,
        });
    </script>

</body>
</html>
