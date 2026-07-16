<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard EO - EvenTour</title>

    @vite(['resources/css/eo/dashboard.css', 'resources/js/app.js'])
</head>

<body class="eo-dashboard-page">

    <div class="bg-glow"></div>

    @include('eo.partials.navbar', ['active' => 'dashboard', 'organizer' => $organizer])

    <main class="main-content">
        <div class="container-custom">

            @if (session('success'))
                <div class="alert alert-success">
                    <x-icon name="check-circle" :size="18" />
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">
                    <x-icon name="alert-triangle" :size="18" />
                    {{ session('error') }}
                </div>
            @endif

            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">DASHBOARD EVENT ORGANIZER</span>
                    <h1>{{ $organizer->org_name }}</h1>
                    <p>Ringkasan performa event dan akses cepat ke menu operasional.</p>
                </div>

                <a href="{{ route('eo.events.create') }}" class="btn-add-event">
                    <x-icon name="circle-plus" :size="18" />
                    Tambah Event
                </a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="check-circle" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Event Disetujui</span>
                        <span class="stat-value">{{ $approvedEventCount }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="clock" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Menunggu Persetujuan</span>
                        <span class="stat-value">{{ $pendingEventCount }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="x-circle" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Ditolak</span>
                        <span class="stat-value">{{ $rejectedEventCount }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="ticket" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Tiket Terjual</span>
                        <span class="stat-value">{{ number_format($ticketSoldCount, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="lock" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Dana Belum Cair</span>
                        <span class="stat-value money">Rp {{ number_format($escrowAmount, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="check-circle" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Sudah Dicairkan</span>
                        <span class="stat-value money">Rp {{ number_format($completedPayoutAmount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-menu-grid">
                <a href="{{ route('eo.events.index') }}" class="dashboard-menu-card">
                    <span class="dashboard-menu-icon"><x-icon name="calendar" :size="26" /></span>
                    <span class="dashboard-menu-body">
                        <strong>Event Saya</strong>
                        <small>{{ $approvedEventCount }} disetujui, {{ $pendingEventCount }} pending</small>
                    </span>
                    <span class="dashboard-menu-action">Buka</span>
                </a>

                <a href="{{ route('eo.payouts.index') }}" class="dashboard-menu-card">
                    <span class="dashboard-menu-icon"><x-icon name="briefcase" :size="26" /></span>
                    <span class="dashboard-menu-body">
                        <strong>Payout</strong>
                        <small>{{ $readyForPayoutCount }} event siap diajukan</small>
                    </span>
                    <span class="dashboard-menu-action">Buka</span>
                </a>

                <a href="{{ route('eo.customers.index') }}" class="dashboard-menu-card">
                    <span class="dashboard-menu-icon"><x-icon name="users" :size="26" /></span>
                    <span class="dashboard-menu-body">
                        <strong>Pembeli</strong>
                        <small>Omzet Rp {{ number_format($grossRevenue, 0, ',', '.') }}</small>
                    </span>
                    <span class="dashboard-menu-action">Buka</span>
                </a>

                <a href="{{ route('eo.scan') }}" class="dashboard-menu-card">
                    <span class="dashboard-menu-icon"><x-icon name="camera" :size="26" /></span>
                    <span class="dashboard-menu-body">
                        <strong>Scan Tiket</strong>
                        <small>Validasi tiket peserta saat check-in</small>
                    </span>
                    <span class="dashboard-menu-action">Buka</span>
                </a>
            </div>

            <div class="card full-width dashboard-finance-strip">
                <div>
                    <span class="badge">RINGKASAN DANA</span>
                    <h2>Transaksi Tiket</h2>
                </div>
                <div class="dashboard-finance-metrics">
                    <div>
                        <span>Total transaksi</span>
                        <strong>Rp {{ number_format($grossRevenue, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <span>Sedang diproses</span>
                        <strong>Rp {{ number_format($processingPayoutAmount, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <span>Bisa diajukan</span>
                        <strong>{{ $readyForPayoutCount }} event</strong>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

</body>
</html>
