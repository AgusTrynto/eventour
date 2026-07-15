<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard EO - EvenTour</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

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
            @if ($errors->any())
                <div class="alert alert-error">
                    <x-icon name="alert-triangle" :size="18" />
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Welcome --}}
            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">DASHBOARD EVENT ORGANIZER</span>
                    <h1>{{ $organizer->org_name }}</h1>
                    <p>Kelola event yang sudah disetujui dan ajukan event baru.</p>
                </div>

                <a href="{{ route('eo.events.create') }}" class="btn-add-event">
                    <x-icon name="circle-plus" :size="18" />
                    Tambah Event
                </a>
            </div>

            {{-- Stats --}}
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="check-circle" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Event Disetujui</span>
                        <span class="stat-value">{{ $approvedEvents->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="clock" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Menunggu Persetujuan</span>
                        <span class="stat-value">{{ $pendingEvents->count() }}</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="x-circle" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Ditolak</span>
                        <span class="stat-value">{{ $rejectedEvents->count() }}</span>
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

            <section class="payout-section" id="payout-section">
                <div class="section-heading">
                    <div>
                        <span class="badge">PAYOUT</span>
                        <h2>Pencairan Dana</h2>
                    </div>
                    <span class="section-subtitle">Dana tiket paid tertahan sampai pengajuan disetujui admin.</span>
                </div>

                <div class="finance-grid">
                    <div class="card finance-card">
                        <div class="card-header">
                            <h2>Rekening Pencairan</h2>
                        </div>
                        <div class="bank-details">
                            <div class="bank-icon"><x-icon name="briefcase" :size="24" /></div>
                            <div>
                                <strong>{{ $organizer->bank_name ?? 'Bank belum diisi' }}</strong>
                                <span>{{ $organizer->bank_channel_code ?? 'Channel belum diisi' }}</span>
                                <span>{{ $organizer->bank_account_number ?? '-' }}</span>
                                <span>a.n. {{ $organizer->bank_account_name ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card finance-card">
                        <div class="card-header">
                            <h2>Ringkasan Dana</h2>
                        </div>
                        <div class="finance-summary">
                            <div>
                                <span>Total transaksi tiket</span>
                                <strong>Rp {{ number_format($grossRevenue, 0, ',', '.') }}</strong>
                            </div>
                            <div>
                                <span>Sedang diproses admin</span>
                                <strong>Rp {{ number_format($processingPayoutAmount, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card full-width">
                    <div class="card-header">
                        <h2>Dana Tertahan Bisa Diajukan ({{ $readyForPayoutEvents->count() }})</h2>
                    </div>

                    @if ($readyForPayoutEvents->isEmpty())
                        <div class="empty-state compact">
                            <span class="empty-state-icon"><x-icon name="check-circle" :size="38" /></span>
                            <p>Belum ada dana tertahan dari tiket paid yang bisa diajukan.</p>
                        </div>
                    @else
                        <div class="event-list">
                            @foreach ($readyForPayoutEvents as $event)
                                <div class="event-item payout-event">
                                    <div class="event-icon pending"><x-icon name="lock" :size="20" /></div>
                                    <div class="event-info">
                                        <span class="event-title">{{ $event->title }}</span>
                                        <span class="event-meta">
                                            Event {{ $event->start_date?->translatedFormat('d M Y') ?? '-' }} -
                                            {{ $event->tickets_sold }} tiket
                                        </span>
                                        <form
                                            action="{{ route('eo.events.payout.request', $event) }}"
                                            method="POST"
                                            enctype="multipart/form-data"
                                            class="payout-request-form"
                                        >
                                            @csrf
                                            <textarea
                                                name="request_reason"
                                                rows="3"
                                                maxlength="1000"
                                                placeholder="Alasan pengajuan, contoh: DP vendor, sewa venue, atau kebutuhan operasional..."
                                                required
                                            ></textarea>
                                            <div class="payout-request-actions">
                                                <label class="payout-file-field">
                                                    <span>Foto pendukung opsional</span>
                                                    <input type="file" name="request_attachment" accept="image/*">
                                                </label>
                                                <button type="submit" class="btn-reviews">
                                                    Ajukan Penarikan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="payout-amount">
                                        <strong>Rp {{ number_format($event->escrow_amount, 0, ',', '.') }}</strong>
                                        <span class="status-badge status-pending">Dana tertahan</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="card full-width">
                    <div class="card-header">
                        <h2>Riwayat Payout</h2>
                    </div>

                    @if ($recentPayouts->isEmpty())
                        <div class="empty-state compact">
                            <span class="empty-state-icon"><x-icon name="inbox" :size="38" /></span>
                            <p>Belum ada payout yang dibuat admin.</p>
                        </div>
                    @else
                        <div class="payout-table">
                            <div class="payout-table-header">
                                <span>Event</span>
                                <span>Status</span>
                                <span>Gross</span>
                                <span>Fee</span>
                                <span>Diterima</span>
                                <span>Reference</span>
                            </div>

                            @foreach ($recentPayouts as $payout)
                                @php
                                    $statusClass = match ($payout->status) {
                                        'completed' => 'status-approved',
                                        'failed', 'rejected' => 'status-rejected',
                                        default => 'status-pending',
                                    };
                                @endphp
                                <div class="payout-table-row">
                                    <span class="event-name">{{ $payout->event->title ?? 'Event tidak ditemukan' }}</span>
                                    <span>
                                        <span class="status-badge {{ $statusClass }}">
                                            {{ $payout->status === 'processing' && $payout->xendit_payout_status ? $payout->xendit_payout_status : ucfirst($payout->status) }}
                                        </span>
                                    </span>
                                    <span>Rp {{ number_format($payout->gross_amount, 0, ',', '.') }}</span>
                                    <span>Rp {{ number_format($payout->platform_fee, 0, ',', '.') }}</span>
                                    <span>Rp {{ number_format($payout->net_amount, 0, ',', '.') }}</span>
                                    <span>
                                        @if ($payout->xendit_payout_reference_id)
                                            {{ $payout->xendit_payout_reference_id }}
                                        @elseif ($payout->transfer_proof)
                                            <a href="{{ asset('storage/' . $payout->transfer_proof) }}" target="_blank" rel="noopener" class="btn-reviews">Lihat</a>
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <div class="content-grid">

                {{-- MAP: event yang sudah approved --}}
                <div class="card">
                    <div class="card-header">
                        <h2>Lokasi Event Disetujui</h2>
                        <span class="card-subtitle">{{ $approvedEvents->count() }} event tampil di map</span>
                    </div>
                    <div id="map" class="map-container"></div>
                </div>

                {{-- PENDING EVENTS --}}
                <div class="card">
                    <div class="card-header">
                        <h2>Menunggu Persetujuan</h2>
                    </div>

                    @if ($pendingEvents->isEmpty())
                        <div class="empty-state">
                            <span class="empty-state-icon"><x-icon name="inbox" :size="38" /></span>
                            <p>Tidak ada event yang menunggu persetujuan.</p>
                        </div>
                    @else
                        <div class="event-list">
                            @foreach ($pendingEvents as $event)
                                <div class="event-item">
                                    <div class="event-icon pending"><x-icon name="clock" :size="20" /></div>
                                    <div class="event-info">
                                        <span class="event-title">{{ $event->title }}</span>
                                        <span class="event-meta">
                                            {{ $event->location_name }} ·
                                            {{ $event->start_date->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                    <span class="status-badge status-pending">Pending</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            {{-- APPROVED EVENTS LIST --}}
            <div class="card full-width">
                <div class="card-header">
                    <h2>Event Disetujui ({{ $approvedEvents->count() }})</h2>
                </div>

                @if ($approvedEvents->isEmpty())
                    <div class="empty-state">
                        <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
                        <p>Belum ada event yang disetujui dan tampil di map.</p>
                        <a href="{{ route('eo.events.create') }}" class="btn-explore">Tambah Event Pertama</a>
                    </div>
                @else
                    <div class="event-table">
                        <div class="event-table-header">
                            <span>Nama Event</span>
                            <span>Tanggal</span>
                            <span>Lokasi</span>
                            <span>Harga</span>
                            <span>Tiket</span>
                            <span>Dana</span>
                            <span>Ulasan</span>
                        </div>

                        @foreach ($approvedEvents as $event)
                            <div class="event-table-row">
                                <span class="event-name">{{ $event->title }}</span>
                                <span>{{ $event->start_date->translatedFormat('d M Y, H:i') }}</span>
                                <span>{{ $event->location_name }}</span>
                                <span>{{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}</span>
                                <span>{{ $event->tickets_sold }} / {{ $event->quota ?? 'Tanpa batas' }}</span>
                                <span>Rp {{ number_format($event->escrow_amount, 0, ',', '.') }}</span>
                                <span>
                                    <a href="{{ route('eo.events.reviews', $event) }}" class="btn-reviews">Lihat</a>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- REJECTED EVENTS (kalau ada) --}}
            @if ($rejectedEvents->isNotEmpty())
                <div class="card full-width">
                    <div class="card-header">
                        <h2>Event Ditolak ({{ $rejectedEvents->count() }})</h2>
                    </div>

                    <div class="event-list">
                        @foreach ($rejectedEvents as $event)
                            <div class="event-item">
                                <div class="event-icon rejected"><x-icon name="x-circle" :size="20" /></div>
                                <div class="event-info">
                                    <span class="event-title">{{ $event->title }}</span>
                                    <span class="event-meta">
                                        {{ $event->location_name }} ·
                                        {{ $event->start_date->translatedFormat('d M Y') }}
                                    </span>
                                    @if ($event->reject_reason)
                                        <span class="event-reason">Alasan: {{ $event->reject_reason }}</span>
                                    @endif
                                </div>
                                <span class="status-badge status-rejected">Ditolak</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        @php
            $mapEvents = $approvedEvents->map(function ($e) {
                return [
                    'title' => $e->title,
                    'lat'   => $e->lat,
                    'lng'   => $e->lng,
                    'date'  => $e->start_date->translatedFormat('d M Y'),
                ];
            });
        @endphp

        const events = @json($mapEvents);

        const defaultLat = -6.2088;
        const defaultLng = 106.8456;

        const map = L.map('map').setView([defaultLat, defaultLng], 5);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://carto.com/">CARTO</a>',
            maxZoom: 19,
        }).addTo(map);

        if (events.length > 0) {
            const bounds = [];

            events.forEach(ev => {
                const marker = L.circleMarker([ev.lat, ev.lng], {
                    radius: 8,
                    fillColor: '#d8ff4f',
                    color: '#0f1117',
                    weight: 2,
                    fillOpacity: 1,
                }).addTo(map).bindPopup(`<strong>${ev.title}</strong><br>${ev.date}`);

                bounds.push([ev.lat, ev.lng]);
            });

            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
        }

    </script>

</body>
</html>
