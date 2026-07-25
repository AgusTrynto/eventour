<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Saya - EvenTour</title>

    @vite(['resources/css/user/dashboard.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="dashboard-page">
    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'dashboard', 'user' => $user])

    <main class="main-content">
        <div class="container-custom">
            <div class="recommendation-page-header">
                <div>
                    <span class="badge">TIKET</span>
                    <h1>Event Yang Diikuti</h1>
                </div>

                <a href="{{ route('dashboard') }}" class="card-link">
                    <x-icon name="compass" :size="14" />
                    Kembali ke dashboard
                </a>
            </div>

            <div class="recommendation-page-summary">
                <span>{{ $orders->count() }} event diikuti</span>
            </div>

            <div class="recommendation-results">
                @forelse ($orders as $order)
                    @php($event = $order->event)
                    @if (!$event) @continue @endif
                    @php($usedTickets = $order->tickets->whereNotNull('checked_in_at')->count())

                    <a href="{{ route('events.show', $event) }}" class="recommendation-result-card" style="text-decoration:none;color:inherit">
                        <div class="recommendation-result-main">
                            <div class="rec-icon"><x-icon name="ticket" :size="22" /></div>

                            <div class="recommendation-result-info">
                                <h2>{{ $event->title }}</h2>
                                <p>
                                    {{ $event->location_name }}
                                    <span aria-hidden="true">&middot;</span>
                                    {{ $event->start_date?->translatedFormat('d M Y') ?? '-' }}
                                </p>

                                <div class="rec-signals">
                                    <span class="rec-signal">{{ $event->category ?? 'Umum' }}</span>
                                    <span class="rec-signal">{{ $event->display_status }}</span>
                                    <span class="rec-signal">{{ $order->quantity }} tiket</span>
                                    @if ($usedTickets > 0)
                                        <span class="rec-signal">{{ $usedTickets }}/{{ $order->quantity }} digunakan</span>
                                    @else
                                        <span class="rec-signal">Belum digunakan</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="recommendation-result-actions">
                            <span class="rec-score">Rp {{ number_format($event->price, 0, ',', '.') }}</span>
                            <span class="rec-btn">Lihat</span>
                        </div>
                    </a>
                @empty
                    <div class="card">
                        <div class="empty-state">
                            <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
                            <p>Belum ada event yang diikuti.</p>
                            <a href="{{ route('dashboard') }}" class="rec-btn" style="margin-top:12px;display:inline-block">Cari Event</a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>
</body>
</html>
