@extends('admin.layout')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard')

@section('content')

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><x-icon name="users" :size="26" /></div>
        <div class="stat-info">
            <span class="stat-label">Total User</span>
            <span class="stat-value">{{ $stats['total_users'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><x-icon name="building" :size="26" /></div>
        <div class="stat-info">
            <span class="stat-label">Total EO</span>
            <span class="stat-value">{{ $stats['total_eo'] }}</span>
        </div>
    </div>
    <div class="stat-card highlight">
        <div class="stat-icon"><x-icon name="clock" :size="26" /></div>
        <div class="stat-info">
            <span class="stat-label">EO Pending</span>
            <span class="stat-value">{{ $stats['pending_eo'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><x-icon name="ticket" :size="26" /></div>
        <div class="stat-info">
            <span class="stat-label">Total Event</span>
            <span class="stat-value">{{ $stats['total_events'] }}</span>
        </div>
    </div>
    <div class="stat-card highlight">
        <div class="stat-icon"><x-icon name="clock" :size="26" /></div>
        <div class="stat-info">
            <span class="stat-label">Event Pending</span>
            <span class="stat-value">{{ $stats['pending_events'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><x-icon name="check-circle" :size="26" /></div>
        <div class="stat-info">
            <span class="stat-label">Event Live</span>
            <span class="stat-value">{{ $stats['approved_events'] }}</span>
        </div>
    </div>
</div>

@php
    $leader = $topOrganizers->first();
@endphp

<div class="card top-eo-card">
    <div class="top-eo-header">
        <div>
            <span class="section-kicker">Penjualan tiket</span>
            <h2>EO Paling Ramai</h2>
            <p>Ranking berdasarkan tiket sukses terjual dari order paid dan disbursed.</p>
        </div>

        @if ($leader)
            <div class="top-eo-pill">
                <x-icon name="star" :size="16" />
                <span>#1 {{ $leader->org_name }}</span>
            </div>
        @endif
    </div>

    @if ($topOrganizers->isNotEmpty())
        <div class="top-eo-layout">
            <div class="top-eo-leader">
                <div class="leader-rank">#1</div>
                <div class="leader-main">
                    <div class="leader-avatar">{{ strtoupper(substr($leader->org_name, 0, 1)) }}</div>
                    <div class="leader-copy">
                        <span>EO Teramai</span>
                        <h3>{{ $leader->org_name }}</h3>
                        <p>{{ $leader->user->email ?? 'Email tidak tersedia' }}</p>
                    </div>
                </div>

                <div class="leader-metrics">
                    <div>
                        <span>Tiket terjual</span>
                        <strong>{{ number_format((int) $leader->tickets_sold_count, 0, ',', '.') }}</strong>
                    </div>
                    <div>
                        <span>Omzet</span>
                        <strong>Rp {{ number_format((float) $leader->revenue_total, 0, ',', '.') }}</strong>
                    </div>
                </div>

                @if ($leader->topSellingEvent)
                    <div class="leader-event">
                        <x-icon name="ticket" :size="18" />
                        <div>
                            <span>Event paling laku</span>
                            <strong>{{ $leader->topSellingEvent->title }}</strong>
                        </div>
                    </div>
                @endif
            </div>

            <div class="top-eo-list">
                @foreach ($topOrganizers as $organizer)
                    <div class="top-eo-row {{ $loop->first ? 'is-leader' : '' }}">
                        <div class="top-eo-rank">{{ $loop->iteration }}</div>
                        <div class="top-eo-row-main">
                            <div class="top-eo-row-head">
                                <span>{{ $organizer->org_name }}</span>
                                <strong>{{ number_format((int) $organizer->tickets_sold_count, 0, ',', '.') }} tiket</strong>
                            </div>
                            <div class="top-eo-progress">
                                <span style="width: {{ $organizer->ticket_share_percent }}%"></span>
                            </div>
                            <div class="top-eo-meta">
                                <span>{{ number_format((int) $organizer->paid_orders_count, 0, ',', '.') }} transaksi</span>
                                <span>{{ number_format((int) $organizer->approved_events_count, 0, ',', '.') }} event live</span>
                                <span>Rp {{ number_format((float) $organizer->revenue_total, 0, ',', '.') }}</span>
                            </div>
                            @if ($organizer->topSellingEvent)
                                <div class="top-eo-event-title">
                                    Top event: {{ $organizer->topSellingEvent->title }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="top-eo-empty">
            <span class="empty-card-icon"><x-icon name="ticket" :size="34" /></span>
            <strong>Belum ada penjualan tiket sukses.</strong>
            <p>Ranking EO akan muncul setelah ada order berstatus paid atau disbursed.</p>
        </div>
    @endif
</div>

<div class="grid-2">

    {{-- Pending EO --}}
    <div class="card">
        <div class="card-header">
            <h2>EO Menunggu Persetujuan</h2>
            <a href="{{ route('admin.eo.index') }}" class="card-link">Lihat semua</a>
        </div>

        @forelse ($recentPendingEO as $eo)
            <div class="list-item">
                <div class="list-avatar">{{ strtoupper(substr($eo->org_name, 0, 1)) }}</div>
                <div class="list-info">
                    <span class="list-title">{{ $eo->org_name }}</span>
                    <span class="list-meta">{{ $eo->user->email }} · {{ $eo->phone }}</span>
                </div>
                <div class="list-actions">
                    <form action="{{ route('admin.eo.approve', $eo) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-approve" title="Setujui">
                            <x-icon name="check-circle" :size="16" />
                        </button>
                    </form>
                    <button type="button" class="btn-reject-open" title="Tolak"
                        onclick="openRejectModal({{ $eo->id }}, '{{ addslashes($eo->org_name) }}', 'eo')">
                        <x-icon name="x" :size="16" />
                    </button>
                </div>
            </div>
        @empty
            <p class="empty-text">Tidak ada EO yang menunggu persetujuan.</p>
        @endforelse
    </div>

    {{-- Pending Events --}}
    <div class="card">
        <div class="card-header">
            <h2>Event Menunggu Persetujuan</h2>
            <a href="{{ route('admin.events.index') }}" class="card-link">Lihat semua</a>
        </div>

        @forelse ($recentPendingEvents as $event)
            <div class="list-item">
                <div class="list-avatar event"><x-icon name="ticket" :size="18" /></div>
                <div class="list-info">
                    <span class="list-title">{{ $event->title }}</span>
                    <span class="list-meta">
                        {{ $event->organizer->org_name }} ·
                        {{ $event->start_date?->translatedFormat('d M Y') ?? '-' }}
                    </span>
                </div>
                <div class="list-actions">
                    <form action="{{ route('admin.events.approve', $event) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-approve" title="Setujui">
                            <x-icon name="check-circle" :size="16" />
                        </button>
                    </form>
                    <button type="button" class="btn-reject-open" title="Tolak"
                        onclick="openRejectModal({{ $event->id }}, '{{ addslashes($event->title) }}', 'event')">
                        <x-icon name="x" :size="16" />
                    </button>
                </div>
            </div>
        @empty
            <p class="empty-text">Tidak ada event yang menunggu persetujuan.</p>
        @endforelse
    </div>

</div>

@endsection
