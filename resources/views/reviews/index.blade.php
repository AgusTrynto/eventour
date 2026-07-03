<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ulasan Event - EvenTour</title>
    @vite(['resources/css/user/reviews.css', 'resources/js/app.js'])
</head>

<body class="reviews-page">

    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">Even<span>Tour</span></a>
        <a href="{{ route('dashboard') }}" class="back-link">← Dashboard</a>
    </header>

    <main class="main-content">
        <div class="container-custom narrow">

            <div class="page-heading">
                <span class="badge">ULASAN</span>
                <h1>Daftar Event yang Sudah Diikuti</h1>
                <p>Beri rating satu kali untuk event yang sudah kamu tonton, lalu lihat riwayat ulasanmu di sini.</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">Periksa kembali rating dan ulasan kamu.</div>
            @endif

            @forelse ($reviewableEvents as $event)
                @php $review = $event->reviews->first(); @endphp

                <section class="review-card">
                    <div class="review-event-header">
                        <div>
                            <h2>{{ $event->title }}</h2>
                            <p>
                                {{ $event->location_name }} ·
                                {{ $event->start_date?->translatedFormat('d M Y, H:i') ?? '-' }}
                            </p>
                        </div>

                        @if ($review)
                            <span class="review-status">Sudah diulas</span>
                        @else
                            <span class="review-status pending">Belum diulas</span>
                        @endif
                    </div>

                    @if ($review)
                        <div class="review-history">
                            <span class="review-history-label">Rating kamu</span>
                            <div class="readonly-stars" aria-label="Rating {{ $review->rating }} dari 5">
                                @for ($rating = 1; $rating <= 5; $rating++)
                                    <span class="{{ $rating <= $review->rating ? 'filled' : '' }}">★</span>
                                @endfor
                            </div>

                            @if ($review->comment)
                                <p class="review-comment">{{ $review->comment }}</p>
                            @else
                                <p class="review-comment muted">Tidak ada ulasan teks.</p>
                            @endif

                            <small class="review-date">Diulas pada {{ $review->created_at->translatedFormat('d M Y, H:i') }}</small>
                        </div>
                    @else
                        <form action="{{ route('reviews.store') }}" method="POST" class="review-form">
                            @csrf
                            <input type="hidden" name="event_id" value="{{ $event->id }}">

                            <fieldset class="star-rating-field">
                                <legend>Pilih rating</legend>
                                <div class="star-rating" aria-label="Pilih rating bintang">
                                    @for ($rating = 5; $rating >= 1; $rating--)
                                        <input type="radio" id="rating-{{ $event->id }}-{{ $rating }}" name="rating" value="{{ $rating }}" required @checked((int) old('event_id') === $event->id && (int) old('rating') === $rating)>
                                        <label for="rating-{{ $event->id }}-{{ $rating }}" title="{{ $rating }} bintang">★</label>
                                    @endfor
                                </div>
                            </fieldset>

                            <label for="comment-{{ $event->id }}">Ulasan</label>
                            <textarea id="comment-{{ $event->id }}" name="comment" rows="4" placeholder="Ceritakan pengalamanmu di event ini...">{{ (int) old('event_id') === $event->id ? old('comment') : '' }}</textarea>

                            <button type="submit" class="btn-submit-review">Kirim Ulasan</button>
                        </form>
                    @endif
                </section>
            @empty
                <div class="empty-state">
                    <span>★</span>
                    <p>Belum ada event yang bisa kamu ulas.</p>
                    <small>Ulasan akan tersedia setelah tiket kamu digunakan/check-in di event.</small>
                    <a href="{{ route('tickets.index') }}" class="btn-explore">Lihat Tiket Saya</a>
                </div>
            @endforelse

        </div>
    </main>

    <footer>© 2026 EvenTour. All Rights Reserved.</footer>

</body>
</html>
