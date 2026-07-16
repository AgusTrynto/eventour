<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout EO - EvenTour</title>

    @vite(['resources/css/eo/dashboard.css', 'resources/js/app.js'])
</head>

<body class="eo-dashboard-page">

    <div class="bg-glow"></div>

    @include('eo.partials.navbar', ['active' => 'payouts', 'organizer' => $organizer])

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

            <div class="welcome-section">
                <div class="welcome-text">
                    <span class="badge">PAYOUT</span>
                    <h1>Pencairan Dana</h1>
                    <p>Dana tiket paid tertahan sampai pengajuan disetujui admin.</p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><x-icon name="ticket" :size="28" /></div>
                    <div class="stat-info">
                        <span class="stat-label">Total Transaksi</span>
                        <span class="stat-value money">Rp {{ number_format($grossRevenue, 0, ',', '.') }}</span>
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

            <section class="payout-section">
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
                                <span>Sedang diproses admin</span>
                                <strong>Rp {{ number_format($processingPayoutAmount, 0, ',', '.') }}</strong>
                            </div>
                            <div>
                                <span>Event siap diajukan</span>
                                <strong>{{ $readyForPayoutEvents->count() }} event</strong>
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

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

</body>
</html>
