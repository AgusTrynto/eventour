<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Saya - EvenTour</title>
    @vite(['resources/css/tickets/tickets.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="ticket-list-page">

    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'tickets'])

    <main class="main-content">
        <div class="container-custom narrow">

            <div class="page-heading">
                <span class="badge">TIKET SAYA</span>
                <h1>Riwayat Tiket</h1>
                <p>Semua tiket yang sudah kamu beli atau klaim.</p>
            </div>

            @forelse ($tickets as $orderId => $group)
                @php $first = $group->first(); @endphp

                <div class="order-group">
                    <div class="order-group-header">
                        <div>
                            <h3>{{ $first->event->title }}</h3>
                            <span class="order-meta">
                                {{ $first->event->start_date?->translatedFormat('d M Y, H:i') ?? '-' }} ·
                                {{ $group->count() }} tiket
                            </span>
                        </div>
                        <span class="order-total">
                            Rp {{ number_format($first->order->total_amount, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="ticket-mini-list">
                        @foreach ($group as $ticket)
                            @php
                                $ticketUrl = route('tickets.show', $ticket);
                                $shareText = "Tiket EvenTour\nEvent: {$ticket->event->title}\nKode: {$ticket->ticket_code}\nDetail: {$ticketUrl}";
                            @endphp

                            <div class="ticket-mini">
                                <a href="{{ $ticketUrl }}" class="ticket-mini-main">
                                    <span class="ticket-mini-code">{{ $ticket->ticket_code }}</span>
                                    <span class="ticket-mini-status status-{{ $ticket->status }}">
                                        @if ($ticket->status === 'valid') Aktif
                                        @elseif ($ticket->status === 'used') Terpakai
                                        @else Batal @endif
                                    </span>
                                </a>

                                <div class="ticket-actions">
                                    <a href="{{ $ticketUrl }}" class="ticket-action-btn">Detail</a>
                                    <a href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener" class="ticket-action-btn whatsapp">Bagikan WA</a>
                                    <button type="button" class="ticket-action-btn" data-ticket-code="{{ $ticket->ticket_code }}" data-event-title="{{ $ticket->event->title }}">
                                        Download QR
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
                    <p>Kamu belum punya tiket apapun.</p>
                    <a href="{{ route('dashboard') }}" class="btn-explore">Jelajahi Event</a>
                </div>
            @endforelse

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        document.querySelectorAll('[data-ticket-code]').forEach(button => {
            button.addEventListener('click', () => {
                const ticketCode = button.dataset.ticketCode;
                const eventTitle = button.dataset.eventTitle || 'event';
                const qrHolder = document.createElement('div');

                qrHolder.style.position = 'fixed';
                qrHolder.style.left = '-9999px';
                qrHolder.style.top = '-9999px';
                document.body.appendChild(qrHolder);

                new QRCode(qrHolder, {
                    text: ticketCode,
                    width: 320,
                    height: 320,
                    colorDark: '#0f1117',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H,
                });

                setTimeout(() => {
                    const canvas = qrHolder.querySelector('canvas');
                    const image = qrHolder.querySelector('img');
                    const dataUrl = canvas ? canvas.toDataURL('image/png') : image?.src;

                    if (!dataUrl) {
                        qrHolder.remove();
                        return;
                    }

                    const link = document.createElement('a');
                    const safeEventTitle = eventTitle.toLowerCase().replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '');

                    link.href = dataUrl;
                    link.download = `qr-${safeEventTitle || 'event'}-${ticketCode}.png`;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    qrHolder.remove();
                }, 100);
            });
        });
    </script>

</body>
</html>
