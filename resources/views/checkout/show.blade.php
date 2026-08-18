<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - {{ $event->title }} - EvenTour</title>
    @vite(['resources/css/checkout/checkout.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="checkout-page">
    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'events'])

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
            @if (session('success'))
                <div class="success-box">{{ session('success') }}</div>
            @endif

            <form action="{{ route('checkout.store', $event) }}" method="POST" class="checkout-form">
                @csrf

                @if ($event->max_tickets_per_person && $event->ticket_purchase_policy === 'strict')
                    <div class="limit-info">
                        <x-icon name="alert-triangle" :size="14" />
                        Maksimal <strong>{{ $event->max_tickets_per_person }}</strong> tiket per orang
                    </div>
                @endif

                <div class="form-group">
                    <label>Jumlah Tiket</label>
                    <div class="qty-control">
                        <button type="button" class="qty-btn" id="qty-minus">−</button>
                        <input type="number" name="quantity" id="qty-input" value="1" min="1"
                            @if ($event->max_tickets_per_person && $event->ticket_purchase_policy === 'strict') max="{{ $event->max_tickets_per_person }}" @endif readonly>
                        <button type="button" class="qty-btn" id="qty-plus">+</button>
                    </div>
                </div>

                <div id="attendee-section" class="attendee-section" style="display: none;">
                    <div class="form-group">
                        <label>Nama Pemegang Tiket <span id="attendee-count-label"></span></label>
                        <div id="attendee-fields"></div>
                        <small class="form-hint">Isi nama lengkap setiap pemegang tiket sesuai KTP untuk keperluan verifikasi di lokasi.</small>
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
        const policy = @json($event->ticket_purchase_policy ?? 'strict');
        const globalMax = {{ $event->max_tickets_per_person ?? 'null' }};
        let maxTicket = 10;
        let strictLimit = 10;

        if (globalMax !== null) {
            strictLimit = globalMax;
            maxTicket = policy === 'strict' ? globalMax : Math.max(globalMax * 3, 20);
        } else {
            maxTicket = policy === 'strict' ? 10 : 20;
        }

        const qtyInput = document.getElementById('qty-input');
        const displayQty = document.getElementById('display-qty');
        const displayTotal = document.getElementById('display-total');
        const attendeeSection = document.getElementById('attendee-section');
        const attendeeFields = document.getElementById('attendee-fields');
        const attendeeCountLabel = document.getElementById('attendee-count-label');

        function formatRupiah(num) {
            return num > 0 ? 'Rp ' + num.toLocaleString('id-ID') : 'Gratis';
        }

        function updateTotal() {
            const qty = parseInt(qtyInput.value);
            displayQty.textContent = qty;
            displayTotal.textContent = formatRupiah(price * qty);
            renderAttendeeFields(qty);
        }

        function renderAttendeeFields(qty) {
            const shouldShow = policy === 'flexible' && globalMax !== null && qty > globalMax;
            const extraCount = Math.max(0, Math.ceil((qty - strictLimit) / strictLimit));

            if (!shouldShow) {
                attendeeSection.style.display = 'none';
                return;
            }

            attendeeSection.style.display = 'block';
            attendeeCountLabel.textContent = `(isi ${extraCount} nama tambahan, sisanya atas nama Anda)` ;

            const existingInputs = attendeeFields.querySelectorAll('input');
            if (existingInputs.length === extraCount) return;

            attendeeFields.innerHTML = '';
            for (let i = 0; i < extraCount; i++) {
                const div = document.createElement('div');
                div.className = 'attendee-input';
                div.style.marginBottom = '10px';
                div.innerHTML = `
                    <label style="font-size:13px;color:#c4c4c4;margin-bottom:4px;display:block;">Pemegang Tiket Tambahan #${i + 1}</label>
                    <input type="text" name="attendee_name[]" placeholder="Nama lengkap sesuai KTP"
                        value=""
                        style="width:100%;padding:10px;border-radius:10px;border:1px solid var(--border,#444);background:rgba(255,255,255,.03);color:#fff;font-size:14px;"
                        required>
                `;
                attendeeFields.appendChild(div);
            }
        }

        document.getElementById('qty-minus').addEventListener('click', () => {
            const val = parseInt(qtyInput.value);
            if (val > 1) { qtyInput.value = val - 1; updateTotal(); }
        });

        document.getElementById('qty-plus').addEventListener('click', () => {
            const val = parseInt(qtyInput.value);
            if (val < maxTicket) { qtyInput.value = val + 1; updateTotal(); }
        });
    </script>

</body>
</html>
