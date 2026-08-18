<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket - {{ $ticket->event->title }} - {{ $holderName }} - EvenTour</title>
    @vite(['resources/css/tickets/tickets.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="ticket-detail-page">

    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'tickets'])

    <main class="main-content">
        <div class="ticket-detail-wrap">
            <div class="ticket-detail-header">
                <a href="{{ route('tickets.index') }}" class="back-link">
                    <x-icon name="arrow-left" :size="16" />
                    Kembali
                </a>

                <span class="badge">QR TIKET</span>
                <h1>{{ $ticket->event->title }}</h1>
                <p>Atas nama <strong>{{ $holderName }}</strong></p>
            </div>

            <div class="ticket-event-summary">
                <div class="info-row">
                    <span><x-icon name="calendar" :size="16" /> Tanggal</span>
                    <span>{{ $ticket->event->start_date?->translatedFormat('d F Y, H:i') ?? '-' }} WIB</span>
                </div>
                <div class="info-row">
                    <span><x-icon name="map-pin" :size="16" /> Lokasi</span>
                    <span>{{ $ticket->event->location_name }}</span>
                </div>
            </div>

            <div class="holder-ticket-grid">
                @forelse ($holderTickets as $holderTicket)
                    <article class="ticket-card holder-ticket-card">
                        <div class="ticket-status status-{{ $holderTicket->status }}">
                            @if ($holderTicket->status === 'valid')
                                <x-icon name="ticket" :size="16" />
                                Tiket Aktif
                            @elseif ($holderTicket->status === 'used')
                                <x-icon name="check-circle" :size="16" />
                                Sudah Digunakan
                            @else
                                <x-icon name="x-circle" :size="16" />
                                Dibatalkan
                            @endif
                        </div>

                        <div class="qr-wrapper">
                            <div class="ticket-qr" data-ticket-code="{{ $holderTicket->ticket_code }}"></div>
                        </div>

                        <p class="ticket-code">{{ $holderTicket->ticket_code }}</p>

                        <div class="ticket-divider">
                            <span></span><span></span>
                        </div>

                        <div class="ticket-info">
                            <div class="info-row">
                                <span><x-icon name="user" :size="16" /> Atas Nama</span>
                                <span>{{ trim((string) $holderTicket->attendee_name) ?: $holderName }}</span>
                            </div>

                            @if ($holderTicket->status === 'used')
                                <div class="info-row checked-in">
                                    <span><x-icon name="check-circle" :size="16" /> Check-in</span>
                                    <span>{{ $holderTicket->checked_in_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state">
                        <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
                        <p>Tiket atas nama ini belum tersedia.</p>
                    </div>
                @endforelse
            </div>

            <p class="ticket-note">
                Tunjukkan QR code ini ke panitia saat masuk ke lokasi event.
            </p>
        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        document.querySelectorAll('[data-ticket-code]').forEach((qrElement) => {
            new QRCode(qrElement, {
                text: qrElement.dataset.ticketCode,
                width: 220,
                height: 220,
                colorDark: "#0f1117",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H,
            });
        });
    </script>

</body>
</html>
