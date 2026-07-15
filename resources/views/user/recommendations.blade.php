<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Rekomendasi - EvenTour</title>

    @vite(['resources/css/user/dashboard.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="dashboard-page">
    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'events', 'user' => $user])

    <main class="main-content">
        <div class="container-custom">
            <div class="recommendation-page-header">
                <div>
                    <span class="badge">EVENT</span>
                    <h1>Rekomendasi Event</h1>
                </div>

                <a href="{{ route('dashboard') }}" class="card-link">
                    <x-icon name="compass" :size="14" />
                    Kembali ke dashboard
                </a>
            </div>

            <div class="recommendation-page-summary">
                <span>{{ $recommendedEvents->total() }} event direkomendasikan</span>
                <span>Halaman {{ $recommendedEvents->currentPage() }} dari {{ max(1, $recommendedEvents->lastPage()) }}</span>
            </div>

            <div class="recommendation-results">
                @forelse ($recommendedEvents as $recommendation)
                    @php($event = $recommendation['event'])

                    <article class="recommendation-result-card">
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
                                    <span class="rec-signal">{{ $recommendation['model_label'] }}</span>
                                    <span class="rec-signal">{{ $recommendation['category_label'] }}</span>
                                    <span class="rec-signal">{{ $recommendation['time_label'] }}</span>
                                    <span class="rec-signal">{{ $recommendation['price_label'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="recommendation-result-actions">
                            <span class="rec-score">{{ $recommendation['score_label'] }}</span>
                            <a href="{{ route('events.show', $event) }}" class="rec-btn">Lihat</a>
                        </div>
                    </article>
                @empty
                    <div class="card">
                        <div class="empty-state">
                            <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
                            <p>Belum ada event yang bisa direkomendasikan.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($recommendedEvents->hasPages())
                <nav class="recommendation-pagination" aria-label="Navigasi halaman rekomendasi">
                    @if ($recommendedEvents->onFirstPage())
                        <span class="pagination-link disabled">Sebelumnya</span>
                    @else
                        <a href="{{ $recommendedEvents->previousPageUrl() }}" class="pagination-link">Sebelumnya</a>
                    @endif

                    @foreach ($recommendedEvents->getUrlRange(1, $recommendedEvents->lastPage()) as $page => $url)
                        @if ($page === $recommendedEvents->currentPage())
                            <span class="pagination-link active">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="pagination-link">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if ($recommendedEvents->hasMorePages())
                        <a href="{{ $recommendedEvents->nextPageUrl() }}" class="pagination-link">Berikutnya</a>
                    @else
                        <span class="pagination-link disabled">Berikutnya</span>
                    @endif
                </nav>
            @endif
        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>
</body>
</html>
