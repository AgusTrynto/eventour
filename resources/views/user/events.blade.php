<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event - EvenTour</title>

    @vite(['resources/css/user/dashboard.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="dashboard-page">
    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'events', 'user' => $user])

    <main class="main-content">
        <div class="container-custom events-page-container">

            {{-- PAGE HEADER --}}
            <div class="recommendation-page-header">
                <div>
                    <span class="badge">EVENT</span>
                    <h1>Jelajahi Event</h1>
                </div>

                <a href="{{ route('dashboard') }}" class="card-link">
                    <x-icon name="compass" :size="14" />
                    Kembali ke dashboard
                </a>
            </div>

            {{-- SECTION 1: 5 REKOMENDASI TERBAIK --}}
            @if ($recommendations->isNotEmpty())
                <section class="events-section">
                    <div class="events-section-header">
                        <div>
                            <h2 class="events-section-title">
                                <span>⭐</span> Rekomendasi Untukmu
                            </h2>
                            <p class="events-section-subtitle">
                                Top {{ $recommendations->count() }} event pilihan berbasis sistem rekomendasi personal Anda
                            </p>
                        </div>
                        <span class="badge" style="background: rgba(216,255,79,.12); color: var(--accent); border-color: rgba(216,255,79,.25);">
                            Rekomendasi Pintar
                        </span>
                    </div>

                    <div class="recommendation-results">
                        @foreach ($recommendations as $recommendation)
                            @php($event = $recommendation['event'])

                            <article class="recommendation-result-card">
                                <div class="recommendation-result-main">
                                    <div class="rec-icon"><x-icon name="ticket" :size="22" /></div>

                                    <div class="recommendation-result-info">
                                        <h2>{{ $event->title }}</h2>
                                        <p>
                                            {{ $event->location_name ?? '-' }}
                                            <span aria-hidden="true">&middot;</span>
                                            {{ $event->start_date?->translatedFormat('d M Y') ?? '-' }}
                                        </p>

                                        <div class="rec-signals">
                                            @if (!empty($recommendation['model_label']))
                                                <span class="rec-signal">{{ $recommendation['model_label'] }}</span>
                                            @endif
                                            @if (!empty($recommendation['category_label']))
                                                <span class="rec-signal">{{ $recommendation['category_label'] }}</span>
                                            @endif
                                            @if (!empty($recommendation['time_label']))
                                                <span class="rec-signal">{{ $recommendation['time_label'] }}</span>
                                            @endif
                                            @if (!empty($recommendation['price_label']))
                                                <span class="rec-signal">{{ $recommendation['price_label'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="recommendation-result-actions">
                                    <a href="{{ route('events.show', $event) }}" class="rec-btn">Lihat Event</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- SECTION 2: DAFTAR SEMUA EVENT (SERVER-SIDE PAGINATION 10 PER HALAMAN) --}}
            <section class="events-section">
                <div class="events-section-header">
                    <div>
                        <h2 class="events-section-title">
                            <span>📅</span> Semua Event
                        </h2>
                        <p class="events-section-subtitle">
                            Menampilkan {{ $events->firstItem() ?? 0 }} - {{ $events->lastItem() ?? 0 }} dari {{ $events->total() }} event tersedia (10 event per halaman)
                        </p>
                    </div>

                    {{-- SEARCH FORM --}}
                    <form method="GET" action="{{ route('dashboard.events') }}" class="events-catalog-search">
                        <div class="events-search-field">
                            <input
                                type="search"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Cari nama event, lokasi..."
                                aria-label="Cari event"
                            >
                            @if (request('q'))
                                <a href="{{ route('dashboard.events') }}" class="events-search-clear" title="Reset pencarian">
                                    <x-icon name="x" :size="14" />
                                </a>
                            @endif
                        </div>
                        <button type="submit" class="rec-btn" style="height: 38px; cursor: pointer;">
                            Cari
                        </button>
                    </form>
                </div>

                <div class="recommendation-results">
                    @forelse ($events as $event)
                        <article class="recommendation-result-card">
                            <div class="recommendation-result-main">
                                <div class="rec-icon">
                                    <x-icon name="calendar" :size="22" />
                                </div>

                                <div class="recommendation-result-info">
                                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                        <h2>{{ $event->title }}</h2>
                                        @if ($event->is_ended)
                                            <span class="suggestion-status ended" style="font-size: 11px;">Berakhir</span>
                                        @elseif ($event->start_date && $event->start_date->gt(now()))
                                            <span class="suggestion-status active events-badge-upcoming" style="font-size: 11px;">Akan Datang</span>
                                        @else
                                            <span class="suggestion-status active" style="font-size: 11px;">Berlangsung</span>
                                        @endif
                                    </div>

                                    <p>
                                        {{ $event->organizer?->org_name ? 'oleh ' . $event->organizer->org_name . ' • ' : '' }}
                                        {{ $event->location_name ?? '-' }}
                                        <span aria-hidden="true">&middot;</span>
                                        {{ $event->start_date?->translatedFormat('d M Y, H:i') ?? '-' }}
                                        @if ($event->end_date)
                                            - {{ $event->end_date->translatedFormat('d M Y, H:i') }}
                                        @endif
                                    </p>

                                    <div class="rec-signals">
                                        <span class="rec-signal">{{ $event->category_emoji }} {{ ucfirst(str_replace('_', ' ', $event->category ?? 'Umum')) }}</span>
                                        <span class="rec-signal" style="color: var(--accent); font-weight: 700;">
                                            {{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}
                                        </span>
                                        @if ($event->quota !== null)
                                            <span class="rec-signal">Kuota: {{ number_format($event->quota, 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="recommendation-result-actions">
                                <a href="{{ route('events.show', $event) }}" class="rec-btn">Lihat Event</a>
                            </div>
                        </article>
                    @empty
                        <div class="card">
                            <div class="empty-state">
                                <span class="empty-state-icon"><x-icon name="calendar" :size="38" /></span>
                                <p>Tidak ada event yang ditemukan{{ request('q') ? ' dengan kata kunci "' . request('q') . '"' : '' }}.</p>
                                @if (request('q'))
                                    <a href="{{ route('dashboard.events') }}" class="card-link" style="margin-top: 10px;">Reset Pencarian</a>
                                @endif
                            </div>
                        </div>
                    @endforelse
                </div>

                {{-- SERVER-SIDE PAGINATION --}}
                @if ($events->hasPages())
                    <nav class="recommendation-pagination" aria-label="Navigasi halaman event" style="margin-top: 24px;">
                        @if ($events->onFirstPage())
                            <span class="pagination-link disabled">Sebelumnya</span>
                        @else
                            <a href="{{ $events->previousPageUrl() }}" class="pagination-link">Sebelumnya</a>
                        @endif

                        @foreach ($events->getUrlRange(1, $events->lastPage()) as $page => $url)
                            @if ($page === $events->currentPage())
                                <span class="pagination-link active">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="pagination-link">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($events->hasMorePages())
                            <a href="{{ $events->nextPageUrl() }}" class="pagination-link">Berikutnya</a>
                        @else
                            <span class="pagination-link disabled">Berikutnya</span>
                        @endif
                    </nav>
                @endif
            </section>
        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>
</body>
</html>

