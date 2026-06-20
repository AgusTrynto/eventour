@extends('admin.layout')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard')

@section('content')

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <span class="stat-label">Total User</span>
            <span class="stat-value">{{ $stats['total_users'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🏢</div>
        <div class="stat-info">
            <span class="stat-label">Total EO</span>
            <span class="stat-value">{{ $stats['total_eo'] }}</span>
        </div>
    </div>
    <div class="stat-card highlight">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
            <span class="stat-label">EO Pending</span>
            <span class="stat-value">{{ $stats['pending_eo'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🎪</div>
        <div class="stat-info">
            <span class="stat-label">Total Event</span>
            <span class="stat-value">{{ $stats['total_events'] }}</span>
        </div>
    </div>
    <div class="stat-card highlight">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
            <span class="stat-label">Event Pending</span>
            <span class="stat-value">{{ $stats['pending_events'] }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-info">
            <span class="stat-label">Event Live</span>
            <span class="stat-value">{{ $stats['approved_events'] }}</span>
        </div>
    </div>
</div>

<div class="grid-2">

    {{-- Pending EO --}}
    <div class="card">
        <div class="card-header">
            <h2>EO Menunggu Persetujuan</h2>
            <a href="{{ route('admin.eo.index') }}" class="card-link">Lihat semua →</a>
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
                        <button type="submit" class="btn-approve" title="Setujui">✓</button>
                    </form>
                    <button type="button" class="btn-reject-open" title="Tolak"
                        onclick="openRejectModal({{ $eo->id }}, '{{ addslashes($eo->org_name) }}', 'eo')">✕</button>
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
            <a href="{{ route('admin.events.index') }}" class="card-link">Lihat semua →</a>
        </div>

        @forelse ($recentPendingEvents as $event)
            <div class="list-item">
                <div class="list-avatar event">🎪</div>
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
                        <button type="submit" class="btn-approve" title="Setujui">✓</button>
                    </form>
                    <button type="button" class="btn-reject-open" title="Tolak"
                        onclick="openRejectModal({{ $event->id }}, '{{ addslashes($event->title) }}', 'event')">✕</button>
                </div>
            </div>
        @empty
            <p class="empty-text">Tidak ada event yang menunggu persetujuan.</p>
        @endforelse
    </div>

</div>

@endsection