<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Saya - EvenTour</title>
    @vite(['resources/css/tickets/tickets.css', 'resources/js/app.js'])
</head>

<body class="ticket-list-page">

    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">Even<span>Tour</span></a>
        <a href="{{ route('dashboard') }}" class="back-link">← Dashboard</a>
    </header>

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
                            <a href="{{ route('tickets.show', $ticket) }}" class="ticket-mini">
                                <span class="ticket-mini-code">{{ $ticket->ticket_code }}</span>
                                <span class="ticket-mini-status status-{{ $ticket->status }}">
                                    @if ($ticket->status === 'valid') Aktif
                                    @elseif ($ticket->status === 'used') Terpakai
                                    @else Batal @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <span>🎫</span>
                    <p>Kamu belum punya tiket apapun.</p>
                    <a href="{{ route('dashboard') }}" class="btn-explore">Jelajahi Event</a>
                </div>
            @endforelse

        </div>
    </main>

    <footer>© 2026 EvenTour. All Rights Reserved.</footer>

</body>
</html>