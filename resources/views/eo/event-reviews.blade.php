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

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            <div class="page-heading">
                <span class="badge">ULASAN EVENT</span>
                <h1>{{ $event->title }}</h1>
                <p>{{ $event->location_name }} · {{ $event->start_date->translatedFormat('d M Y, H:i') }}</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="star" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Rating Rata-rata</span>
                        <span class="stat-value">{{ $averageRating ?? '-' }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="message-circle" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Total Ulasan</span>
                        <span class="stat-value">{{ $reviewCount }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="ticket" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Tiket Terjual</span>
                        <span class="stat-value">{{ $event->tickets_sold }}</span>
                    </div>
                </div>
            </div>

            <a href="{{ route('eo.dashboard') }}" class="btn-explore">
                <x-icon name="arrow-left" :size="16" />
                Kembali ke Dashboard
            </a>

            <section class="ai-summary-card">
                <div class="ai-summary-header">
                    <div>
                        <span class="badge">ANALISIS AI</span>
                        <h2>Kesimpulan Ulasan</h2>
                        @if ($reviewSummary)
                            <p>
                                Diperbarui {{ $reviewSummary->generated_at?->translatedFormat('d M Y, H:i') }}
                                dari {{ $reviewSummary->review_count }} ulasan.
                            </p>
                        @else
                            <p>Belum ada kesimpulan tersimpan untuk event ini.</p>
                        @endif
                    </div>

                    <form action="{{ route('eo.events.reviews.summary', $event) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-refresh-summary" @disabled($reviewCount === 0)>
                            <x-icon name="refresh" :size="16" />
                            Refresh Kesimpulan
                        </button>
                    </form>
                </div>

                @if ($reviewSummary)
                    @if ($reviewSummary->review_count !== $reviewCount)
                        <div class="summary-notice">Ada perubahan jumlah ulasan sejak kesimpulan terakhir. Tekan refresh untuk memperbarui analisis.</div>
                    @endif

                    <div class="summary-body">
                        <div class="summary-main">
                            <span class="summary-label">Ringkasan Utama</span>
                            <p>{{ $reviewSummary->summary }}</p>
                        </div>

                        <div class="summary-meta-grid">
                            <div>
                                <span class="summary-label">Sentimen</span>
                                <strong>{{ ucfirst($reviewSummary->sentiment ?? '-') }}</strong>
                            </div>
                            <div>
                                <span class="summary-label">Rating Saat Analisis</span>
                                <strong>{{ $reviewSummary->average_rating ? number_format((float) $reviewSummary->average_rating, 1, ',', '.') : '-' }}</strong>
                            </div>
                        </div>

                        <div class="summary-columns">
                            <div class="summary-list-block">
                                <span class="summary-label">Poin Positif</span>
                                <ul>
                                    @forelse ($reviewSummary->positive_points ?? [] as $point)
                                        <li>{{ $point }}</li>
                                    @empty
                                        <li class="muted">Belum ada poin positif spesifik.</li>
                                    @endforelse
                                </ul>
                            </div>

                            <div class="summary-list-block">
                                <span class="summary-label">Keluhan Utama</span>
                                <ul>
                                    @forelse ($reviewSummary->negative_points ?? [] as $point)
                                        <li>{{ $point }}</li>
                                    @empty
                                        <li class="muted">Belum ada keluhan spesifik.</li>
                                    @endforelse
                                </ul>
                            </div>

                            <div class="summary-list-block">
                                <span class="summary-label">Rekomendasi EO</span>
                                <ul>
                                    @forelse ($reviewSummary->recommendations ?? [] as $point)
                                        <li>{{ $point }}</li>
                                    @empty
                                        <li class="muted">Belum ada rekomendasi spesifik.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="summary-empty">
                        <p>Tekan Refresh Kesimpulan untuk membuat analisis dari ulasan yang sudah masuk.</p>
                    </div>
                @endif
            </section>

            @if ($reviewCount === 0)
                <div class="empty-state">
                    <span class="empty-state-icon"><x-icon name="message-circle" :size="38" /></span>
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
                                        <span class="{{ $rating <= $review->rating ? 'filled' : '' }}">&#9733;</span>
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

                @if ($reviews->hasPages())
                    <div class="review-pagination">
                        <span>
                            Menampilkan {{ $reviews->firstItem() }}-{{ $reviews->lastItem() }}
                            dari {{ $reviews->total() }} ulasan
                        </span>

                        <div class="pagination-actions">
                            @if ($reviews->onFirstPage())
                                <span class="pagination-btn disabled">Sebelumnya</span>
                            @else
                                <a href="{{ $reviews->previousPageUrl() }}" class="pagination-btn">Sebelumnya</a>
                            @endif

                            <span class="pagination-current">Halaman {{ $reviews->currentPage() }} / {{ $reviews->lastPage() }}</span>

                            @if ($reviews->hasMorePages())
                                <a href="{{ $reviews->nextPageUrl() }}" class="pagination-btn">Berikutnya</a>
                            @else
                                <span class="pagination-btn disabled">Berikutnya</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endif

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

</body>
</html>
