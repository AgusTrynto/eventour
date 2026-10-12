<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - EvenTour</title>
    @vite(['resources/css/checkout/checkout.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
    <style>
        .order-list { width: 100%; margin: 1.25rem 0; }
        .order-item { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: .75rem; }
        .order-item-name { font-weight: 600; font-size: .95rem; color: #e5e7eb; margin-bottom: .5rem; }
        .order-detail-row { display: flex; justify-content: space-between; font-size: 13px;
            color: #9ca3af; padding: 2px 0; }
        .order-detail-row span:last-child { color: #d1d5db; }
        .total-row { display: flex; justify-content: space-between; font-size: 1rem;
            font-weight: 700; padding: 14px 0 0; border-top: 1px solid rgba(255,255,255,.1);
            margin-top: .5rem; }
        .total-row span:last-child { color: #d8ff4f; }
        .status-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 12px; }
        .status-paid { background: rgba(34,197,94,.15); color: #86efac; border: 1px solid rgba(34,197,94,.25); }
        .status-pending { background: rgba(234,179,8,.15); color: #fde047; border: 1px solid rgba(234,179,8,.25); }
    </style>
</head>

<body class="checkout-page">
    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'tickets'])

    <main class="main-content">
        <div class="checkout-card result-card">

            <div class="result-icon success">
                <x-icon name="check-circle" :size="44" />
            </div>

            <h1>
                @if($orders->every(fn($o) => $o->payment_status === 'paid'))
                    Pembayaran Berhasil!
                @else
                    Pesanan Diterima!
                @endif
            </h1>

            <p style="color: #9ca3af; margin-bottom: 1.5rem;">
                Kamu berhasil memesan tiket untuk <strong>{{ $orders->count() }}</strong> event dalam 1 transaksi.
            </p>

            @if(session('success'))
                <div style="background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.25); color: #86efac;
                    padding: 12px 16px; border-radius: 10px; margin-bottom: 1rem; font-size: 14px;">
                    {{ session('success') }}
                </div>
            @endif

            <div class="order-list">
                @foreach($orders as $order)
                    <div class="order-item">
                        <div class="order-item-name">{{ $order->event->title }}</div>
                        <div class="order-detail-row">
                            <span>Jumlah Tiket</span>
                            <span>{{ $order->quantity }}</span>
                        </div>
                        <div class="order-detail-row">
                            <span>Subtotal</span>
                            <span>
                                {{ $order->subtotal_amount > 0 ? 'Rp ' . number_format($order->subtotal_amount, 0, ',', '.') : 'Gratis' }}
                            </span>
                        </div>
                        <div class="order-detail-row">
                            <span>Status</span>
                            <span>
                                <span class="status-badge {{ $order->payment_status === 'paid' ? 'status-paid' : 'status-pending' }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="total-row">
                <span>Total Transaksi</span>
                <span>{{ $totalAmount > 0 ? 'Rp ' . number_format($totalAmount, 0, ',', '.') : 'Gratis' }}</span>
            </div>

            <div style="margin-top: 1.75rem; display: flex; flex-direction: column; gap: .75rem;">
                <a href="{{ route('tickets.index') }}" class="btn-pay" style="text-decoration: none; text-align: center;">
                    Lihat Tiket Saya
                </a>
                <a href="{{ route('dashboard') }}" style="text-align: center; color: #9ca3af; font-size: .875rem; text-decoration: none;">
                    Kembali ke Dashboard
                </a>
            </div>

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>
</body>
</html>

