<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Keranjang Belanja - EvenTour</title>
    @vite(['resources/css/checkout/checkout.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
    <style>
        .cart-item-card {
            background: rgba(255,255,255,.02);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            transition: border-color .15s;
        }
        .cart-item-card.has-error {
            border-color: rgba(239,68,68,.4);
            background: rgba(239,68,68,.02);
        }
        .cart-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }
        .event-title {
            font-size: 16px;
            font-weight: 700;
            color: #f3f4f6;
            margin-bottom: 4px;
        }
        .event-meta {
            font-size: 12px;
            color: #9ca3af;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn-remove {
            background: transparent;
            border: 1px solid rgba(239,68,68,.4);
            color: #f87171;
            padding: 4px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            transition: all .15s;
            white-space: nowrap;
        }
        .btn-remove:hover {
            background: rgba(239,68,68,.15);
        }
        .cart-item-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 10px;
            border-top: 1px solid rgba(255,255,255,.06);
        }
        .price-info {
            font-size: 14px;
            color: #d1d5db;
        }
        .price-subtotal {
            font-size: 15px;
            font-weight: 700;
            color: #d8ff4f;
        }
        .qty-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qty-btn {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            color: inherit;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s;
        }
        .qty-btn:hover:not(:disabled) {
            background: rgba(255,255,255,.15);
        }
        .qty-btn:disabled {
            opacity: .35;
            cursor: not-allowed;
        }
        .qty-val {
            min-width: 32px;
            text-align: center;
            font-weight: 700;
            font-size: 14px;
        }
        .identity-badge {
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.4;
        }
        .badge-self {
            background: rgba(34,197,94,.08);
            border: 1px solid rgba(34,197,94,.25);
            color: #86efac;
        }
        .badge-warning {
            background: rgba(245,158,11,.08);
            border: 1px solid rgba(245,158,11,.25);
            color: #fcd34d;
        }
        .badge-danger {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.25);
            color: #fca5a5;
        }
        .attendee-form-wrapper {
            margin-top: 12px;
            padding: 14px;
            border-radius: 12px;
            background: rgba(255,255,255,.02);
            border: 1px solid rgba(245,158,11,.25);
        }
        .attendee-block {
            margin-top: 10px;
            padding: 12px;
            border-radius: 10px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.06);
        }
        .attendee-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 6px;
        }
        @media(max-width: 600px) {
            .attendee-grid { grid-template-columns: 1fr; }
        }
        .form-input-sm {
            width: 100%;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04);
            color: #fff;
            font-size: 13px;
            box-sizing: border-box;
        }
        .form-input-sm:focus {
            outline: none;
            border-color: #d8ff4f;
            background: rgba(255,255,255,.07);
        }
        .cart-empty { text-align: center; padding: 3rem 0; color: #9ca3af; }
        .cart-empty .empty-icon { font-size: 3rem; margin-bottom: 1rem; }
        .summary-box {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 14px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 14px;
        }
        .summary-row.total {
            border-top: 1px solid rgba(255,255,255,.1);
            margin-top: 8px;
            padding-top: 14px;
            font-size: 16px;
            font-weight: 700;
        }
        .summary-row.total span:last-child { color: #d8ff4f; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 1.25rem; font-size: 14px; }
        .alert-success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.25); color: #86efac; }
        .alert-error { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; }
    </style>
</head>

<body class="checkout-page">
    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'events'])

    <main class="main-content">
        <div class="checkout-card" style="max-width: 800px;">

            <h1 style="font-size: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: .5rem;">
                🛒 Keranjang Belanja
                @if($cartItems->isNotEmpty())
                    <span style="font-size: .875rem; font-weight: 400; color: #9ca3af;">({{ $cartItems->count() }} event)</span>
                @endif
            </h1>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @if($cartItems->isEmpty())
                <div class="cart-empty">
                    <div class="empty-icon">🛒</div>
                    <p style="font-size: 1rem; margin-bottom: .5rem;">Keranjang kamu kosong</p>
                    <p style="font-size: .875rem;">Jelajahi event dan tambahkan tiket yang kamu inginkan.</p>
                    <a href="{{ route('dashboard.events') }}" class="btn-pay" style="display: inline-block; margin-top: 1.5rem; text-decoration: none;">
                        Jelajahi Event
                    </a>
                </div>
            @else
                <form id="checkout-form" action="{{ route('cart.checkout') }}" method="POST" data-no-page-loader>
                    @csrf

                    @php
                        $hasStrictViolation = false;
                    @endphp

                    @foreach($cartItems as $item)
                        @php
                            $event = $item->event;
                            $remSlots = $item->user_remaining_slots;
                            $userOwned = $event->getUserOwnedTicketsCount($user);
                            $reqCount = $item->required_attendee_count;
                            $isStrictBlocked = ($event->ticket_purchase_policy === 'strict' && $remSlots !== null && ($remSlots === 0 || $item->quantity > $remSlots));
                            if ($isStrictBlocked) {
                                $hasStrictViolation = true;
                            }
                        @endphp

                        <div class="cart-item-card {{ $isStrictBlocked ? 'has-error' : '' }}" id="card-{{ $item->id }}"
                             data-item-id="{{ $item->id }}"
                             data-price="{{ $event->price }}"
                             data-policy="{{ $event->ticket_purchase_policy ?? 'strict' }}"
                             data-max-per-person="{{ $event->max_tickets_per_person ?? 'null' }}"
                             data-remaining-slots="{{ $remSlots !== null ? $remSlots : 'null' }}"
                             data-user-owned="{{ $userOwned }}">

                            <div class="cart-item-header">
                                <div>
                                    <div class="event-title">{{ $event->title }}</div>
                                    <div class="event-meta">
                                        <span>📅 {{ $event->start_date?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</span>
                                        <span>📍 {{ $event->location_name }}</span>
                                    </div>
                                </div>
                                <button type="button" class="btn-remove" onclick="submitDelete({{ $item->id }})">
                                    Hapus
                                </button>
                            </div>

                            <div class="cart-item-body">
                                <div class="price-info">
                                    Harga: {{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}
                                    &nbsp;|&nbsp;
                                    Subtotal: <span class="price-subtotal" id="sub-{{ $item->id }}">
                                        {{ $item->subtotal > 0 ? 'Rp ' . number_format($item->subtotal, 0, ',', '.') : 'Gratis' }}
                                    </span>
                                </div>

                                <div class="qty-control">
                                    <button type="button" class="qty-btn qty-minus" data-item="{{ $item->id }}">−</button>
                                    <span class="qty-val" id="qty-val-{{ $item->id }}">{{ $item->quantity }}</span>
                                    <button type="button" class="qty-btn qty-plus" data-item="{{ $item->id }}">+</button>
                                </div>
                            </div>

                            {{-- STATUS IDENTITAS PEMEGANG TIKET --}}
                            @if($isStrictBlocked)
                                <div class="identity-badge badge-danger">
                                    ⚠️ <strong>Batas Kuota Tercapai:</strong> Anda sudah memiliki tiket event ini hingga batas maksimal ({{ $event->max_tickets_per_person }} tiket). Event ini menerapkan aturan ketat (Strict), sehingga tiket tidak dapat dipesan untuk orang lain. Silakan hapus event ini dari keranjang.
                                </div>
                            @else
                                {{-- Badge otomatis nama diri sendiri (muncul jika hanya 1 tiket dan belum pernah punya) --}}
                                <div class="identity-badge badge-self" id="self-badge-{{ $item->id }}" style="{{ $reqCount > 0 ? 'display: none;' : '' }}">
                                    ✓ <strong>Otomatis Atas Nama Anda:</strong> Tiket akan diterbitkan menggunakan identitas profil Anda (<strong>{{ $user->name }}</strong> - NIK: <strong>{{ $user->nik ?: 'Belum diisi di profil' }}</strong>).
                                    @if(empty($user->nik) && $event->max_tickets_per_person !== null)
                                        <br><span style="color: #fcd34d; font-size: 12px;">⚠️ NIK profil Anda masih kosong. Silakan <a href="{{ route('profile.edit') }}" style="color: #d8ff4f; text-decoration: underline;">lengkapi NIK di profil</a> sebelum checkout.</span>
                                    @endif
                                </div>

                                {{-- Wrapper form data pemegang tiket tambahan / orang lain --}}
                                <div id="attendee-wrapper-{{ $item->id }}" class="attendee-form-wrapper" style="{{ $reqCount === 0 ? 'display: none;' : '' }}">
                                    <div style="font-weight: 700; color: #fbbf24; font-size: 13px; margin-bottom: 4px;">
                                        ⚠️ Data Pemegang Tiket Tambahan (Wajib Diisi)
                                    </div>
                                    <div style="font-size: 12px; color: #9ca3af; margin-bottom: 8px;" id="attendee-desc-{{ $item->id }}">
                                        @if($userOwned > 0)
                                            Anda sudah memiliki tiket di event ini. Seluruh tiket wajib didaftarkan atas nama orang lain / teman.
                                        @else
                                            1 tiket pertama otomatis menggunakan data profil Anda. {{ max(1, $item->quantity - 1) }} tiket selebihnya wajib didaftarkan atas nama orang lain / teman.
                                        @endif
                                    </div>

                                    <div id="attendee-inputs-{{ $item->id }}">
                                        @for($i = 0; $i < $reqCount; $i++)
                                            <div class="attendee-block">
                                                <div style="font-size: 12px; font-weight: 600; color: #e5e7eb; margin-bottom: 4px;">
                                                    {{ $userOwned > 0 ? "Pemegang Tiket #".($i + 1) : "Pemegang Tiket Tambahan #".($i + 1) }}
                                                </div>
                                                <div class="attendee-grid">
                                                    <div>
                                                        <label style="font-size: 11px; color: #9ca3af; display: block; margin-bottom: 2px;">Nama Lengkap (KTP)</label>
                                                        <input type="text" name="attendees[{{ $item->id }}][name][]"
                                                               value="{{ old("attendees.{$item->id}.name.{$i}", $item->attendee_details[$i]['name'] ?? '') }}"
                                                               placeholder="Nama lengkap sesuai KTP" class="form-input-sm" required>
                                                    </div>
                                                    <div>
                                                        <label style="font-size: 11px; color: #9ca3af; display: block; margin-bottom: 2px;">NIK (16 Digit)</label>
                                                        <input type="text" name="attendees[{{ $item->id }}][nik][]"
                                                               value="{{ old("attendees.{$item->id}.nik.{$i}", $item->attendee_details[$i]['nik'] ?? '') }}"
                                                               placeholder="16 digit NIK" maxlength="30" class="form-input-sm" required>
                                                    </div>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            @endif

                        </div>
                    @endforeach

                    {{-- Summary Box --}}
                    <div class="summary-box">
                        @foreach($cartItems as $item)
                            <div class="summary-row" id="sum-row-{{ $item->id }}">
                                <span>{{ $item->event->title }} × <span id="sum-qty-{{ $item->id }}">{{ $item->quantity }}</span></span>
                                <span id="sum-sub-{{ $item->id }}">{{ $item->subtotal > 0 ? 'Rp ' . number_format($item->subtotal, 0, ',', '.') : 'Gratis' }}</span>
                            </div>
                        @endforeach

                        <div class="summary-row">
                            <span>Biaya admin per transaksi</span>
                            <span id="display-admin-fee">{{ $adminFee > 0 ? 'Rp ' . number_format($adminFee, 0, ',', '.') : 'Gratis' }}</span>
                        </div>

                        <div class="summary-row total">
                            <span>Total Bayar</span>
                            <span id="display-total">{{ $total > 0 ? 'Rp ' . number_format($total, 0, ',', '.') : 'Gratis' }}</span>
                        </div>
                    </div>

                    <div class="escrow-note" style="margin-bottom: 1.5rem;">
                        <x-icon name="shield" :size="16" />
                        Dana kamu ditahan aman oleh EvenTour sampai event terverifikasi berlangsung.
                    </div>

                    <button type="submit" class="btn-pay" id="btn-checkout" {{ $hasStrictViolation ? 'disabled' : '' }}>
                        {{ $hasStrictViolation ? 'Selesaikan Masalah Kuota Tiket Terlebih Dahulu' : 'Lanjutkan ke Pembayaran' }}
                    </button>
                </form>

                {{-- Hidden Delete Forms (to avoid nested forms) --}}
                @foreach($cartItems as $item)
                    <form id="delete-form-{{ $item->id }}" action="{{ route('cart.destroy', $item) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    <script>
        const csrfToken = document.querySelector('meta[name=csrf-token]')?.content || '';
        const adminFeeBase = {{ \App\Models\Order::ADMIN_FEE }};
        const userName = @json($user->name ?? 'Anda');
        const userNik = @json($user->nik ?? '');

        function formatRupiah(num) {
            return num > 0 ? 'Rp ' + num.toLocaleString('id-ID') : 'Gratis';
        }

        function submitDelete(itemId) {
            const form = document.getElementById('delete-form-' + itemId);
            if (form) form.submit();
        }

        function calculateCartTotals() {
            let subtotalAll = 0;
            let hasPaid = false;

            document.querySelectorAll('.cart-item-card').forEach(card => {
                const itemId = card.dataset.itemId;
                const price = parseFloat(card.dataset.price) || 0;
                const qtyValEl = document.getElementById('qty-val-' + itemId);
                const qty = parseInt(qtyValEl ? qtyValEl.textContent : 1) || 1;
                const sub = price * qty;
                subtotalAll += sub;
                if (price > 0) hasPaid = true;
            });

            const adminFee = hasPaid ? adminFeeBase : 0;
            const total = subtotalAll + adminFee;

            const feeEl = document.getElementById('display-admin-fee');
            if (feeEl) feeEl.textContent = formatRupiah(adminFee);

            const totalEl = document.getElementById('display-total');
            if (totalEl) totalEl.textContent = formatRupiah(total);
        }

        function updateAttendeeFields(card, qty) {
            const itemId = card.dataset.itemId;
            const policy = card.dataset.policy;
            const userOwned = parseInt(card.dataset.userOwned) || 0;

            const selfBadge = document.getElementById('self-badge-' + itemId);
            const wrapper = document.getElementById('attendee-wrapper-' + itemId);
            const descEl = document.getElementById('attendee-desc-' + itemId);
            const inputsCont = document.getElementById('attendee-inputs-' + itemId);

            if (!wrapper || !inputsCont) return;

            if (policy === 'strict') {
                return;
            }

            // Hitung kebutuhan orang lain:
            let reqCount = 0;
            if (userOwned > 0) {
                // User sudah pernah punya tiket di event ini, semua tiket untuk orang lain
                reqCount = qty;
            } else {
                // User belum pernah punya tiket: 1 tiket untuk diri sendiri, selebihnya (qty - 1) untuk orang lain
                reqCount = Math.max(0, qty - 1);
            }

            if (reqCount <= 0) {
                wrapper.style.display = 'none';
                if (selfBadge) {
                    selfBadge.style.display = 'block';
                }
                inputsCont.innerHTML = '';
                return;
            }

            if (selfBadge) selfBadge.style.display = 'none';
            wrapper.style.display = 'block';

            if (descEl) {
                descEl.textContent = userOwned > 0
                    ? `Anda sudah memiliki tiket di event ini. Seluruh tiket wajib didaftarkan atas nama orang lain / teman.`
                    : `1 tiket pertama otomatis menggunakan data profil Anda. ${qty - 1} tiket selebihnya wajib didaftarkan atas nama orang lain / teman.`;
            }

            // Simpan data input yang sudah diketik pengguna agar tidak ter-reset
            const existingBlocks = inputsCont.querySelectorAll('.attendee-block');
            const savedData = [];
            existingBlocks.forEach(block => {
                const nameInp = block.querySelector('input[name*="[name]"]');
                const nikInp = block.querySelector('input[name*="[nik]"]');
                savedData.push({
                    name: nameInp ? nameInp.value : '',
                    nik: nikInp ? nikInp.value : ''
                });
            });

            // Render form blok sesuai reqCount
            inputsCont.innerHTML = '';
            for (let i = 0; i < reqCount; i++) {
                const label = userOwned > 0 ? `Pemegang Tiket #${i + 1}` : `Pemegang Tiket Tambahan #${i + 1}`;
                const prev = savedData[i] || { name: '', nik: '' };
                const div = document.createElement('div');
                div.className = 'attendee-block';
                div.innerHTML = `
                    <div style="font-size: 12px; font-weight: 600; color: #e5e7eb; margin-bottom: 4px;">${label}</div>
                    <div class="attendee-grid">
                        <div>
                            <label style="font-size: 11px; color: #9ca3af; display: block; margin-bottom: 2px;">Nama Lengkap (KTP)</label>
                            <input type="text" name="attendees[${itemId}][name][]" value="${prev.name}" placeholder="Nama lengkap sesuai KTP" class="form-input-sm" required>
                        </div>
                        <div>
                            <label style="font-size: 11px; color: #9ca3af; display: block; margin-bottom: 2px;">NIK (16 Digit)</label>
                            <input type="text" name="attendees[${itemId}][nik][]" value="${prev.nik}" placeholder="16 digit NIK" maxlength="30" class="form-input-sm" required>
                        </div>
                    </div>
                `;
                inputsCont.appendChild(div);
            }
        }

        function updateQty(itemId, delta) {
            const card = document.getElementById('card-' + itemId);
            if (!card) return;

            const price = parseFloat(card.dataset.price) || 0;
            const policy = card.dataset.policy;
            const remSlots = card.dataset.remainingSlots !== 'null' ? parseInt(card.dataset.remainingSlots) : null;

            const qtyValEl = document.getElementById('qty-val-' + itemId);
            let currentQty = parseInt(qtyValEl.textContent) || 1;
            let newQty = currentQty + delta;

            if (newQty < 1) newQty = 1;
            if (policy === 'strict' && remSlots !== null && newQty > remSlots) {
                alert(`Maksimal ${remSlots} tiket untuk event ini (Kebijakan Strict).`);
                return;
            }
            if (newQty > 20) newQty = 20;

            qtyValEl.textContent = newQty;

            // Update subtotal display
            const subEl = document.getElementById('sub-' + itemId);
            if (subEl) subEl.textContent = formatRupiah(price * newQty);

            const sumQtyEl = document.getElementById('sum-qty-' + itemId);
            if (sumQtyEl) sumQtyEl.textContent = newQty;

            const sumSubEl = document.getElementById('sum-sub-' + itemId);
            if (sumSubEl) sumSubEl.textContent = formatRupiah(price * newQty);

            // Update attendee inputs dynamically
            updateAttendeeFields(card, newQty);

            // Update summary totals
            calculateCartTotals();

            // Send PATCH request ke server
            fetch(`/cart/${itemId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ quantity: newQty }),
            }).catch(console.error);
        }

        document.querySelectorAll('.qty-minus').forEach(btn => {
            btn.addEventListener('click', () => updateQty(btn.dataset.item, -1));
        });

        document.querySelectorAll('.qty-plus').forEach(btn => {
            btn.addEventListener('click', () => updateQty(btn.dataset.item, 1));
        });
    </script>
</body>
</html>
