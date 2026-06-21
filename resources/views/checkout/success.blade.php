<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - EvenTour</title>
    @vite(['resources/css/checkout/checkout.css', 'resources/js/app.js'])
</head>

<body class="checkout-page">
    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">Even<span>Tour</span></a>
    </header>

    <main class="main-content">
        <div class="checkout-card result-card">
            <div class="result-icon success">✅</div>
            <h1>Pembayaran Berhasil!</h1>
            <p>Tiket kamu untuk <strong>{{ $order->event->title }}</strong> sudah dikonfirmasi.</p>

            <div class="order-details">
                <div class="detail-row"><span>ID Pesanan</span><span>#{{ $order->id }}</span></div>
                <div class="detail-row"><span>Jumlah Tiket</span><span>{{ $order->quantity }}</span></div>
                <div class="detail-row"><span>Total Bayar</span><span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span></div>
                <div class="detail-row"><span>Status</span><span class="status-paid">{{ ucfirst($order->payment_status) }}</span></div>
            </div>

            <a href="/dashboard" class="btn-pay">Kembali ke Dashboard</a>
        </div>
    </main>

    <footer>© 2026 EvenTour. All Rights Reserved.</footer>
</body>
</html>