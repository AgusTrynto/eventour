<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan {{ $event->title }} - EvenTour</title>
    @vite(['resources/css/eo/dashboard.css', 'resources/js/app.js'])
</head>

<body class="eo-dashboard-page">

    <div class="bg-glow"></div>

    <header class="navbar">
        <div class="container-custom">
            <a href="/dashboard" class="logo">Even<span>Tour</span></a>
            <nav class="nav-links">
                <a href="{{ route('eo.dashboard') }}" class="nav-link">Dashboard EO</a>
                <a href="{{ route('eo.events.reviews', $event) }}" class="nav-link active">Ulasan Event</a>
            </nav>
            <div class="nav-right">
                <span class="user-name">{{ $event->organizer->org_name }}</span>
                <span class="role-badge">EO</span>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container-custom narrow">

            <div class="page-heading">
                <span class="badge">ULASAN EVENT</span>
                <h1>{{ $event->title }}</h1>
                <p>{{ $event->location_name }} · {{ $event->start_date->translatedFormat('d M Y, H:i') }}</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-info">
                        <span class="stat-label">Rating Rata-rata</span>
                        <span class="stat-value">{{ $averageRating ?? '-' }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💬</div>
                    <div class="stat-info">
                        <span class="stat-label">Total Ulasan</span>
                        <span class="stat-value">{{ $reviews->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎟️</div>
                    <div class="stat-info">
                        <span class="stat-label">Tiket Terjual</span>
                        <span class="stat-value">{{ $event->tickets_sold }}</span>
                    </div>
                </div>
            </div>

            <a href="{{ route('eo.dashboard') }}" class="btn-explore">← Kembali ke Dashboard</a>

            @if ($reviews->isEmpty())
                <div class="empty-state">
                    <span>💬</span>
                    <p>Belum ada ulasan untuk event ini.</p>
                    <small>Ulasan akan muncul setelah peserta check-in dan memberikan penilaian.</small>
                </div>
            @else
                <div class="review-list">
                    @foreach ($reviews as $review)
                        <div class="review-item">
                            <div class="review-user">
                                <span class="review-avatar">{{ strtoupper(substr($review->user->name, 0, 1)) }}</span>
                                <div class="review-user-info">
                                    <span class="review-user-name">{{ $review->user->name }}</span>
                                    <span class="review-date">{{ $review->created_at->translatedFormat('d M Y, H:i') }}</span>
                                </div>
                                <div class="review-stars">
                                    @for ($rating = 1; $rating <= 5; $rating++)
                                        <span class="{{ $rating <= $review->rating ? 'filled' : '' }}">★</span>
                                    @endfor
                                </div>
                            </div>
                            @if ($review->comment)
                                <p class="review-comment">{{ $review->comment }}</p>
                            @else
                                <p class="review-comment muted">Tidak ada ulasan teks.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </main>

    <footer>© 2026 EvenTour. All Rights Reserved.</footer>

</body>
</html>
