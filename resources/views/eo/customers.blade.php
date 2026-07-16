<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembeli EO - EvenTour</title>

    @vite(['resources/css/eo/dashboard.css', 'resources/js/app.js'])
</head>

<body class="eo-dashboard-page">

    <div class="bg-glow"></div>

    @include('eo.partials.navbar', ['active' => 'customers', 'organizer' => $organizer])

    <main class="main-content">
        <div class="container-custom">

            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">PEMBELI</span>
                    <h1>Top Spender</h1>
                    <p>Lihat pembeli dengan total transaksi tertinggi dari event yang kamu adakan.</p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="ticket" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Tiket Terjual</span>
                        <span class="stat-value">{{ number_format($ticketSoldCount, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="briefcase" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Total Transaksi</span>
                        <span class="stat-value money">Rp {{ number_format($grossRevenue, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="users" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Pembeli Teratas</span>
                        <span class="stat-value">{{ $topSpenders->count() }}</span>
                    </div>
                </div>
            </div>

            @php
                $topSpenderLeader = $topSpenders->first();
            @endphp

            <div class="card top-spender-card">
                <div class="top-spender-header">
                    <div>
                        <span class="badge">TOP SPENDER</span>
                        <h2>Pembeli Tiket Teratas</h2>
                        <p>User dengan total belanja tiket tertinggi dari event yang kamu adakan.</p>
                    </div>

                    @if ($topSpenderLeader)
                        <div class="top-spender-pill">
                            <x-icon name="star" :size="16" />
                            <span>{{ $topSpenderLeader->user->name ?? 'User tidak ditemukan' }}</span>
                        </div>
                    @endif
                </div>

                @if ($topSpenders->isNotEmpty())
                    @php
                        $leaderName = $topSpenderLeader->user->name ?? 'User tidak ditemukan';
                    @endphp

                    <div class="top-spender-layout">
                        <div class="spender-leader">
                            <div class="spender-leader-rank">#1</div>
                            <div class="spender-leader-profile">
                                <div class="spender-avatar">{{ strtoupper(substr($leaderName, 0, 1)) }}</div>
                                <div>
                                    <span>Pembeli paling loyal</span>
                                    <h3>{{ $leaderName }}</h3>
                                    <p>{{ $topSpenderLeader->user->email ?? 'Email tidak tersedia' }}</p>
                                </div>
                            </div>

                            <div class="spender-leader-metrics">
                                <div>
                                    <span>Total belanja</span>
                                    <strong>Rp {{ number_format((float) $topSpenderLeader->total_spent, 0, ',', '.') }}</strong>
                                </div>
                                <div>
                                    <span>Tiket dibeli</span>
                                    <strong>{{ number_format((int) $topSpenderLeader->tickets_bought, 0, ',', '.') }}</strong>
                                </div>
                                <div>
                                    <span>Transaksi</span>
                                    <strong>{{ number_format((int) $topSpenderLeader->orders_count, 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="spender-list">
                            @foreach ($topSpenders as $spender)
                                @php
                                    $spenderName = $spender->user->name ?? 'User tidak ditemukan';
                                @endphp

                                <div class="spender-row {{ $loop->first ? 'is-leader' : '' }}">
                                    <div class="spender-rank">{{ $loop->iteration }}</div>
                                    <div class="spender-row-main">
                                        <div class="spender-row-head">
                                            <span>{{ $spenderName }}</span>
                                            <strong>Rp {{ number_format((float) $spender->total_spent, 0, ',', '.') }}</strong>
                                        </div>
                                        <div class="spender-progress">
                                            <span style="width: {{ $spender->spend_share_percent }}%"></span>
                                        </div>
                                        <div class="spender-meta">
                                            <span>{{ number_format((int) $spender->tickets_bought, 0, ',', '.') }} tiket</span>
                                            <span>{{ number_format((int) $spender->orders_count, 0, ',', '.') }} transaksi</span>
                                            <span>{{ $spender->user->email ?? 'Email tidak tersedia' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="top-spender-empty">
                        <span class="empty-state-icon"><x-icon name="user" :size="36" /></span>
                        <strong>Belum ada pembeli tiket sukses.</strong>
                        <p>Top spender akan muncul setelah ada tiket yang berhasil dibeli.</p>
                    </div>
                @endif
            </div>

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

</body>
</html>
