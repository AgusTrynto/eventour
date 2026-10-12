<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tiket – {{ $event->title }} - EvenTour</title>
    @vite(['resources/css/eo/dashboard.css', 'resources/js/app.js'])
    <style>
        /* ── Statistik tiket ──────────────────────────────── */
        .ticket-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .ticket-stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .ticket-stat-card .tsc-label {
            font-size: 12px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .ticket-stat-card .tsc-value {
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
            color: white;
        }

        .ticket-stat-card .tsc-value.used    { color: var(--accent); }
        .ticket-stat-card .tsc-value.valid   { color: #60a5fa; }
        .ticket-stat-card .tsc-value.cancelled { color: #f87171; }

        .ticket-stat-card .tsc-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }

        /* ── Progress check-in ───────────────────────────── */
        .checkin-progress-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 28px;
        }

        .checkin-progress-card h2 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 14px;
        }

        .progress-bar-wrap {
            background: rgba(255,255,255,.08);
            border-radius: 999px;
            height: 12px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar-fill {
            background: var(--accent);
            border-radius: 999px;
            height: 100%;
            transition: width .6s ease;
        }

        .progress-legend {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 13px;
            color: #9ca3af;
        }

        .progress-legend span { display: flex; align-items: center; gap: 6px; }
        .progress-legend .dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .dot-used      { background: var(--accent); }
        .dot-valid     { background: #60a5fa; }
        .dot-cancelled { background: #f87171; }

        /* ── Search bar ──────────────────────────────────── */
        .search-bar-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            background: rgba(255,255,255,.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: white;
            font-family: inherit;
            font-size: 14px;
            padding: 9px 14px;
            outline: none;
            transition: border-color .2s;
        }

        .search-input::placeholder { color: #6b7280; }
        .search-input:focus { border-color: rgba(216,255,79,.4); }

        .btn-search {
            background: var(--accent);
            border: none;
            border-radius: 8px;
            color: #0f1117;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            padding: 9px 20px;
            transition: opacity .2s;
            white-space: nowrap;
        }
        .btn-search:hover { opacity: .85; }

        .btn-clear-search {
            background: rgba(255,255,255,.07);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #9ca3af;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            padding: 9px 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: background .2s;
        }
        .btn-clear-search:hover { background: rgba(255,255,255,.12); color: white; }

        /* ── Tabel tiket discan ──────────────────────────── */
        .scanned-table-wrap {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }

        .scanned-table-header-row {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .scanned-table-header-row h2 { font-size: 16px; font-weight: 600; }

        .scanned-table-header-row .subtitle { font-size: 13px; color: #9ca3af; }

        .scanned-table {
            width: 100%;
            border-collapse: collapse;
        }

        .scanned-table thead th {
            background: rgba(255,255,255,.04);
            border-bottom: 1px solid var(--border);
            color: #9ca3af;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .05em;
            padding: 12px 16px;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .scanned-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }

        .scanned-table tbody tr:last-child { border-bottom: none; }

        .scanned-table tbody tr:hover { background: rgba(255,255,255,.03); }

        .scanned-table tbody td {
            color: #d1d5db;
            font-size: 14px;
            padding: 12px 16px;
            vertical-align: middle;
        }

        .scanned-table .td-code {
            font-family: monospace;
            font-size: 13px;
            color: var(--accent);
            letter-spacing: .04em;
        }

        .scanned-table .td-name { font-weight: 500; color: white; }

        .scanned-table .td-time { color: #6b7280; font-size: 13px; }

        .badge-used {
            background: rgba(216,255,79,.12);
            border: 1px solid rgba(216,255,79,.25);
            border-radius: 999px;
            color: var(--accent);
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            white-space: nowrap;
        }

        /* ── Pagination ──────────────────────────────────── */
        .pagination-wrap {
            padding: 16px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .pagination-info { font-size: 13px; color: #6b7280; }

        .pagination-actions { display: flex; gap: 8px; }

        .pagination-btn {
            background: rgba(255,255,255,.07);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #d1d5db;
            cursor: pointer;
            font-size: 13px;
            padding: 6px 14px;
            text-decoration: none;
            transition: background .2s;
        }
        .pagination-btn:hover { background: rgba(255,255,255,.14); color: white; }
        .pagination-btn.disabled { opacity: .35; pointer-events: none; }

        .pagination-current { font-size: 13px; color: #9ca3af; align-self: center; }

        /* ── Back button ─────────────────────────────────── */
        .btn-back {
            align-items: center;
            background: rgba(255,255,255,.07);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #d1d5db;
            display: inline-flex;
            font-size: 14px;
            gap: 7px;
            margin-bottom: 28px;
            padding: 8px 16px;
            text-decoration: none;
            transition: background .2s, color .2s;
        }
        .btn-back:hover { background: rgba(255,255,255,.13); color: white; }

        /* ── Page heading ────────────────────────────────── */
        .page-heading {
            margin-bottom: 28px;
        }
        .page-heading h1 {
            font-size: 26px;
            font-weight: 700;
            margin: 6px 0 4px;
        }
        .page-heading p { color: #9ca3af; font-size: 14px; }

        .empty-scanned {
            padding: 48px 24px;
            text-align: center;
            color: #6b7280;
        }
        .empty-scanned svg { margin-bottom: 12px; }
        .empty-scanned p { font-size: 15px; }
        .empty-scanned small { font-size: 13px; }

        @media (max-width: 640px) {
            .ticket-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .scanned-table thead { display: none; }
            .scanned-table tbody td {
                display: block;
                padding: 6px 16px;
            }
            .scanned-table tbody tr {
                padding: 10px 0;
                display: block;
            }
        }
    </style>
</head>

<body class="eo-dashboard-page">

    <div class="bg-glow"></div>

    @include('eo.partials.navbar', ['active' => 'events', 'organizer' => $event->organizer])

    <main class="main-content">
        <div class="container-custom" style="flex-direction:column; align-items:stretch; height:auto; gap:0; padding-top:32px; padding-bottom:48px;">

            {{-- Kembali --}}
            <a href="{{ route('eo.events.index') }}" class="btn-back">
                <x-icon name="arrow-left" :size="15" />
                Kembali ke Event Saya
            </a>

            {{-- Judul halaman --}}
            <div class="page-heading">
                <span class="badge">DETAIL TIKET</span>
                <h1>{{ $event->title }}</h1>
                <p>
                    {{ $event->location_name }}
                    &nbsp;·&nbsp;
                    {{ $event->start_date->translatedFormat('d M Y, H:i') }}
                    @if ($event->end_date)
                        &nbsp;–&nbsp;{{ $event->end_date->translatedFormat('d M Y, H:i') }}
                    @endif
                    &nbsp;·&nbsp;
                    <span style="color: {{ $event->display_status === 'Berlangsung' ? 'var(--accent)' : 'inherit' }}">
                        {{ $event->display_status }}
                    </span>
                </p>
            </div>

            {{-- Statistik tiket --}}
            <div class="ticket-stats-grid">
                <div class="ticket-stat-card">
                    <span class="tsc-icon">🎟️</span>
                    <span class="tsc-label">Total Tiket Diterbitkan</span>
                    <span class="tsc-value">{{ number_format($totalTickets, 0, ',', '.') }}</span>
                </div>
                <div class="ticket-stat-card">
                    <span class="tsc-icon">✅</span>
                    <span class="tsc-label">Sudah Di-scan (Check-in)</span>
                    <span class="tsc-value used">{{ number_format($usedTickets, 0, ',', '.') }}</span>
                </div>
                <div class="ticket-stat-card">
                    <span class="tsc-icon">🔵</span>
                    <span class="tsc-label">Belum Di-scan (Valid)</span>
                    <span class="tsc-value valid">{{ number_format($validTickets, 0, ',', '.') }}</span>
                </div>
                <div class="ticket-stat-card">
                    <span class="tsc-icon">❌</span>
                    <span class="tsc-label">Dibatalkan / Refund</span>
                    <span class="tsc-value cancelled">{{ number_format($cancelledTickets, 0, ',', '.') }}</span>
                </div>
                <div class="ticket-stat-card">
                    <span class="tsc-icon">📊</span>
                    <span class="tsc-label">Tingkat Check-in</span>
                    <span class="tsc-value used">{{ $checkInRate }}%</span>
                </div>
                <div class="ticket-stat-card">
                    <span class="tsc-icon">💺</span>
                    <span class="tsc-label">Kuota Event</span>
                    <span class="tsc-value" style="font-size:24px;">
                        {{ $event->quota ? number_format($event->quota, 0, ',', '.') : 'Tak terbatas' }}
                    </span>
                </div>
            </div>

            {{-- Progress bar check-in --}}
            <div class="checkin-progress-card">
                <h2>Progress Check-in Peserta</h2>
                @php
                    $activeTickets = $validTickets + $usedTickets;
                    $usedPct    = $activeTickets > 0 ? round(($usedTickets / $activeTickets) * 100) : 0;
                    $validPct   = $activeTickets > 0 ? round(($validTickets / $activeTickets) * 100) : 0;
                @endphp
                <div style="margin-bottom:8px; font-size:13px; color:#9ca3af;">
                    {{ $usedTickets }} dari {{ $activeTickets }} tiket aktif sudah check-in
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" style="width: {{ $usedPct }}%"></div>
                </div>
                <div class="progress-legend">
                    <span><span class="dot dot-used"></span>Sudah check-in: {{ $usedTickets }} ({{ $usedPct }}%)</span>
                    <span><span class="dot dot-valid"></span>Belum check-in: {{ $validTickets }} ({{ $validPct }}%)</span>
                    @if ($cancelledTickets > 0)
                        <span><span class="dot dot-cancelled"></span>Dibatalkan: {{ $cancelledTickets }}</span>
                    @endif
                </div>
            </div>

            {{-- Tabel tiket yang sudah di-scan --}}
            <div class="scanned-table-wrap">
                <div class="scanned-table-header-row">
                    <div>
                        <h2>Tiket Sudah Di-scan</h2>
                        <span class="subtitle">
                            {{ $scannedTickets->total() }} peserta telah check-in
                            @if ($search)
                                · Hasil pencarian: "<strong>{{ $search }}</strong>"
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Kotak pencarian --}}
                <div style="padding: 16px 20px; border-bottom: 1px solid var(--border);">
                    <form class="search-bar-form" method="GET" action="{{ route('eo.events.detail', $event) }}">
                        <input
                            type="text"
                            name="search"
                            class="search-input"
                            placeholder="Cari nama, NIK, atau kode tiket..."
                            value="{{ $search }}"
                        >
                        <button type="submit" class="btn-search">
                            <x-icon name="search" :size="14" />
                            Cari
                        </button>
                        @if ($search)
                            <a href="{{ route('eo.events.detail', $event) }}" class="btn-clear-search">
                                <x-icon name="x" :size="14" />
                                Reset
                            </a>
                        @endif
                    </form>
                </div>

                @if ($scannedTickets->isEmpty())
                    <div class="empty-scanned">
                        <x-icon name="scan-line" :size="40" />
                        <p>
                            @if ($search)
                                Tidak ada tiket ditemukan untuk pencarian "<strong>{{ $search }}</strong>".
                            @else
                                Belum ada tiket yang di-scan.
                            @endif
                        </p>
                        <small>Tiket akan muncul di sini setelah EO melakukan scan QR peserta.</small>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="scanned-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Kode Tiket</th>
                                    <th>Nama Peserta</th>
                                    <th>NIK</th>
                                    <th>Waktu Check-in</th>
                                    <th>Discan Oleh</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($scannedTickets as $index => $ticket)
                                    <tr>
                                        <td class="td-time">{{ $scannedTickets->firstItem() + $index }}</td>
                                        <td class="td-code">{{ $ticket->ticket_code }}</td>
                                        <td class="td-name">
                                            {{ $ticket->attendee_name ?: ($ticket->user?->name ?? '-') }}
                                        </td>
                                        <td class="td-time">
                                            {{ $ticket->attendee_nik ?: ($ticket->user?->nik ?? '-') }}
                                        </td>
                                        <td class="td-time">
                                            @if ($ticket->checked_in_at)
                                                {{ $ticket->checked_in_at->translatedFormat('d M Y, H:i') }}
                                            @else
                                                <span style="color:#4b5563;">—</span>
                                            @endif
                                        </td>
                                        <td class="td-time">
                                            {{ $ticket->checkedInBy?->name ?? '—' }}
                                        </td>
                                        <td>
                                            <span class="badge-used">✓ Check-in</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($scannedTickets->hasPages())
                        <div class="pagination-wrap">
                            <span class="pagination-info">
                                Menampilkan {{ $scannedTickets->firstItem() }}–{{ $scannedTickets->lastItem() }}
                                dari {{ $scannedTickets->total() }} peserta
                            </span>

                            <div class="pagination-actions">
                                @if ($scannedTickets->onFirstPage())
                                    <span class="pagination-btn disabled">← Sebelumnya</span>
                                @else
                                    <a href="{{ $scannedTickets->previousPageUrl() }}" class="pagination-btn">← Sebelumnya</a>
                                @endif

                                <span class="pagination-current">
                                    Halaman {{ $scannedTickets->currentPage() }} / {{ $scannedTickets->lastPage() }}
                                </span>

                                @if ($scannedTickets->hasMorePages())
                                    <a href="{{ $scannedTickets->nextPageUrl() }}" class="pagination-btn">Berikutnya →</a>
                                @else
                                    <span class="pagination-btn disabled">Berikutnya →</span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

</body>
</html>

