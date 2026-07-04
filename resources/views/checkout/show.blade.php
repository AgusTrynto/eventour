<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - {{ $event->title }} - EvenTour</title>
    @vite(['resources/css/checkout/checkout.css', 'resources/js/app.js'])
</head>

<body class="checkout-page">
    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">Even<span>Tour</span></a>
        <a href="{{ url()->previous() }}" class="back-link">
            <x-icon name="arrow-left" :size="16" />
            Kembali
        </a>
    </header>

    <main class="main-content">
        <div class="checkout-card">

            <div class="event-summary">
                <span class="badge">{{ $event->category ?? 'Event' }}</span>
                <h1>{{ $event->title }}</h1>
                <div class="event-meta">
                    <span><x-icon name="calendar" :size="16" /> {{ $event->start_date?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                    <span><x-icon name="map-pin" :size="16" /> {{ $event->location_name }}</span>
                </div>
            </div>

            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <form action="{{ route('checkout.store', $event) }}" method="POST" class="checkout-form">
                @csrf

                <div class="form-group">
                    <label>Jumlah Tiket</label>
                    <div class="qty-control">
                        <button type="button" class="qty-btn" id="qty-minus">−</button>
                        <input type="number" name="quantity" id="qty-input" value="1" min="1" max="10" readonly>
                        <button type="button" class="qty-btn" id="qty-plus">+</button>
                    </div>
                </div>

                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Harga satuan</span>
                        <span>{{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}</span>
                    </div>
                    <div class="price-row">
                        <span>Jumlah tiket</span>
                        <span id="display-qty">1</span>
                    </div>
                    <div class="price-row total">
                        <span>Total Bayar</span>
                        <span id="display-total">
                            {{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}
                        </span>
                    </div>
                </div>

                <div class="escrow-note">
                    <x-icon name="shield" :size="16" />
                    Dana kamu ditahan aman oleh EvenTour sampai event terverifikasi berlangsung.
                    Jika event terbukti tidak valid, dana akan dikembalikan penuh.
                </div>

                <button type="submit" class="btn-pay">
                    {{ $event->price > 0 ? 'Lanjutkan Pembayaran' : 'Klaim Tiket Gratis' }}
                </button>
            </form>

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    <script>
        const price = {{ $event->price }};
        const qtyInput = document.getElementById('qty-input');
        const displayQty = document.getElementById('display-qty');
        const displayTotal = document.getElementById('display-total');

        function formatRupiah(num) {
            return num > 0 ? 'Rp ' + num.toLocaleString('id-ID') : 'Gratis';
        }

        function updateTotal() {
            const qty = parseInt(qtyInput.value);
            displayQty.textContent = qty;
            displayTotal.textContent = formatRupiah(price * qty);
        }

        document.getElementById('qty-minus').addEventListener('click', () => {
            const val = parseInt(qtyInput.value);
            if (val > 1) { qtyInput.value = val - 1; updateTotal(); }
        });

        document.getElementById('qty-plus').addEventListener('click', () => {
            const val = parseInt(qtyInput.value);
            if (val < 10) { qtyInput.value = val + 1; updateTotal(); }
        });
    </script>

</body>
</html>
