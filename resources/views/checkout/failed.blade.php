<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Gagal - EvenTour</title>
    @vite(['resources/css/checkout/checkout.css', 'resources/js/app.js'])
</head>

<body class="checkout-page">
    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">Even<span>Tour</span></a>
    </header>

    <main class="main-content">
        <div class="checkout-card result-card">
            <div class="result-icon failed"><x-icon name="x-circle" :size="44" /></div>
            <h1>Pembayaran Gagal</h1>
            <p>Pembayaran untuk <strong>{{ $order->event->title }}</strong> belum berhasil. Kamu bisa mencoba checkout ulang.</p>

            <div class="order-details">
                <div class="detail-row"><span>ID Pesanan</span><span>#{{ $order->id }}</span></div>
                <div class="detail-row"><span>Jumlah Tiket</span><span>{{ $order->quantity }}</span></div>
                <div class="detail-row"><span>Total Bayar</span><span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span></div>
                <div class="detail-row"><span>Status</span><span class="status-failed">{{ ucfirst($order->payment_status) }}</span></div>
            </div>

            <a href="{{ route('checkout.show', $order->event) }}" class="btn-pay">Coba Checkout Lagi</a>
        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>
</body>
</html>
