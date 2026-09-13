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
                        @if ($userRemainingSlots > 0)
                            Maksimal <strong>{{ $event->max_tickets_per_person }}</strong> tiket per orang. Anda bisa beli <strong>{{ $userRemainingSlots }}</strong> lagi.
                        @else
                            Anda sudah mencapai batas maksimal tiket untuk event ini.
                        @endif
                    </div>
                @endif

                <div class="form-group">
                    <label>Jumlah Tiket</label>
                    <div class="qty-control">
                        <button type="button" class="qty-btn" id="qty-minus">−</button>
                        <input type="number" name="quantity" id="qty-input" value="1" min="1"
                            @if ($event->max_tickets_per_person && $event->ticket_purchase_policy === 'strict') max="{{ max(1, $userRemainingSlots) }}" @endif readonly>
                        <button type="button" class="qty-btn" id="qty-plus">+</button>
                    </div>
                </div>

                <div id="attendee-section" class="attendee-section" style="display: none;">
                    <div class="form-group">
                        <label>Data Pemegang Tiket <span id="attendee-count-label"></span></label>
                        <div id="attendee-fields"></div>
                        <small class="form-hint">Isi nama lengkap dan NIK setiap pemegang tiket sesuai KTP untuk verifikasi di lokasi.</small>
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
                    <div class="price-row">
                        <span>Subtotal tiket</span>
                        <span id="display-subtotal">
                            {{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}
                        </span>
                    </div>
                    <div class="price-row">
                        <span>Biaya admin per transaksi</span>
                        <span id="display-admin-fee">
                            {{ $event->price > 0 ? 'Rp ' . number_format(\App\Models\Order::ADMIN_FEE, 0, ',', '.') : 'Gratis' }}
                        </span>
                    </div>
                    <div class="price-row total">
                        <span>Total Bayar</span>
                        <span id="display-total">
                            {{ $event->price > 0 ? 'Rp ' . number_format($event->price + \App\Models\Order::ADMIN_FEE, 0, ',', '.') : 'Gratis' }}
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
        const adminFee = {{ \App\Models\Order::ADMIN_FEE }};
        const policy = @json($event->ticket_purchase_policy ?? 'strict');
        const globalMax = {{ $event->max_tickets_per_person ?? 'null' }};
        const userRemainingSlots = {{ $userRemainingSlots ?? 'null' }};
        let maxTicket = 10;

        if (globalMax !== null) {
            maxTicket = policy === 'strict' ? userRemainingSlots : Math.max(globalMax * 3, 20);
            if (maxTicket === 0 && policy === 'strict') maxTicket = 1; // Prevent qty 0 on input if strict and maxed out
        } else {
            maxTicket = policy === 'strict' ? 10 : 20;
        }

        const qtyInput = document.getElementById('qty-input');
        const displayQty = document.getElementById('display-qty');
        const displaySubtotal = document.getElementById('display-subtotal');
        const displayAdminFee = document.getElementById('display-admin-fee');
        const displayTotal = document.getElementById('display-total');
        const attendeeSection = document.getElementById('attendee-section');
        const attendeeFields = document.getElementById('attendee-fields');
        const attendeeCountLabel = document.getElementById('attendee-count-label');

        function formatRupiah(num) {
            return num > 0 ? 'Rp ' + num.toLocaleString('id-ID') : 'Gratis';
        }

        function updateTotal() {
            const qty = parseInt(qtyInput.value);
            const subtotal = price * qty;
            const fee = subtotal > 0 ? adminFee : 0;
            displayQty.textContent = qty;
            displaySubtotal.textContent = formatRupiah(subtotal);
            displayAdminFee.textContent = formatRupiah(fee);
            displayTotal.textContent = formatRupiah(subtotal + fee);
            renderAttendeeFields(qty);
        }

        function renderAttendeeFields(qty) {
            // Kalau userRemainingSlots === 0, semua tiket harus atas nama orang lain
            // Kalau userRemainingSlots > 0, hanya tiket melebihi sisa slot saja yang butuh data teman
            const isMaxedOut = userRemainingSlots !== null && userRemainingSlots === 0;
            const isFlexible = policy === 'flexible' && globalMax !== null;

            let requiredCount = 0;
            let countLabel = '';

            if (isFlexible && userRemainingSlots !== null) {
                if (isMaxedOut) {
                    // Semua tiket atas nama orang lain
                    requiredCount = Math.ceil(qty / globalMax);
                    countLabel = `(${requiredCount} orang, semua atas nama teman — Anda sudah capai limit)`;
                } else if (qty > userRemainingSlots) {
                    // Hanya tiket yang melampaui sisa slot yang butuh data teman
                    const excess = qty - userRemainingSlots;
                    requiredCount = Math.ceil(excess / globalMax);
                    countLabel = `(${requiredCount} orang tambahan, ${userRemainingSlots} tiket pertama atas nama Anda)`;
                }
            }

            if (requiredCount <= 0) {
                attendeeSection.style.display = 'none';
                return;
            }

            attendeeSection.style.display = 'block';
            attendeeCountLabel.textContent = countLabel;

            // Rebuild hanya jika jumlah blok berubah
            const existingBlocks = attendeeFields.querySelectorAll('.attendee-input');
            if (existingBlocks.length === requiredCount) return;

            attendeeFields.innerHTML = '';
            const fieldStyle = 'width:100%;padding:10px;border-radius:10px;border:1px solid var(--border,#444);background:rgba(255,255,255,.03);color:inherit;font-size:14px;box-sizing:border-box;';
            for (let i = 0; i < requiredCount; i++) {
                const div = document.createElement('div');
                div.className = 'attendee-input';
                div.style.cssText = 'margin-bottom:16px;padding:14px;border-radius:12px;border:1px solid rgba(255,255,255,.06);background:rgba(255,255,255,.02);';
                const holderLabel = isMaxedOut ? `Pemegang Tiket #${i + 1}` : `Pemegang Tiket Tambahan #${i + 1}`;
                div.innerHTML = `
                    <p style="font-size:13px;font-weight:700;color:#c4c4c4;margin-bottom:10px;">${holderLabel}</p>
                    <div style="margin-bottom:8px;">
                        <label style="font-size:12px;color:#9ca3af;margin-bottom:4px;display:block;">Nama Lengkap</label>
                        <input type="text" name="attendee_name[]" placeholder="Nama lengkap sesuai KTP"
                            style="${fieldStyle}" required>
                    </div>
                    <div>
                        <label style="font-size:12px;color:#9ca3af;margin-bottom:4px;display:block;">NIK</label>
                        <input type="text" name="attendee_nik[]" placeholder="16 digit NIK"
                            maxlength="30" style="${fieldStyle}" required>
                    </div>
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

        // Initialize state on page load
        updateTotal();
    </script>

</body>
</html>
