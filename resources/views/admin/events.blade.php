@extends('admin.layout')

@section('title', 'Manajemen Event')
@section('page-title', 'Persetujuan Event')

@section('content')

{{-- TABS --}}
<div class="tabs">
    <button class="tab active" data-tab="pending">
        Pending <span class="tab-count">{{ $pendingEvents->count() }}</span>
    </button>
    <button class="tab" data-tab="approved">
        Disetujui <span class="tab-count approved">{{ $approvedEvents->count() }}</span>
    </button>
    <button class="tab" data-tab="rejected">
        Ditolak <span class="tab-count rejected">{{ $rejectedEvents->count() }}</span>
    </button>
</div>

{{-- PENDING --}}
<div class="tab-content active" id="tab-pending">
    @forelse ($pendingEvents as $event)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $event->title }}</h3>
                    <span class="event-card-by">oleh {{ $event->organizer->org_name }}</span>
                </div>
                <span class="status-badge status-pending">Pending</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Kategori</span>
                    <span>{{ $event->category ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal</span>
                    <span>
                        {{ $event->start_date?->translatedFormat('d M Y, H:i') ?? '-' }}
                        @if ($event->end_date)
                            - {{ $event->end_date->translatedFormat('d M Y, H:i') }}
                        @endif
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Lokasi</span>
                    <span>{{ $event->location_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Harga</span>
                    <span>{{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Kuota</span>
                    <span>{{ $event->quota ?? 'Tidak terbatas' }}</span>
                </div>
                @if ($event->description)
                    <div class="detail-row full">
                        <span class="detail-label">Deskripsi</span>
                        <p class="detail-desc">{{ $event->description }}</p>
                    </div>
                @endif
            </div>

            <div class="eo-actions">
                <form action="{{ route('admin.events.approve', $event) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-approve-full">
                        <x-icon name="check-circle" :size="17" />
                        Setujui & Tampilkan di Map
                    </button>
                </form>
                <button type="button" class="btn-reject-full"
                    onclick="openRejectModal({{ $event->id }}, '{{ addslashes($event->title) }}', 'event')">
                    <x-icon name="x" :size="17" />
                    Tolak
                </button>
            </div>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="check-circle" :size="38" /></span>
            <p>Tidak ada event yang menunggu persetujuan.</p>
        </div>
    @endforelse
</div>

{{-- APPROVED --}}
<div class="tab-content" id="tab-approved">
    <div class="event-table">
        <div class="event-table-header">
            <span>Nama Event</span>
            <span>EO</span>
            <span>Tanggal</span>
            <span>Lokasi</span>
            <span>Harga</span>
            <span>Dana Paid</span>
            <span>Refund</span>
        </div>
        @forelse ($approvedEvents as $event)
            @php
                $paidOrdersCount = (int) ($event->paid_orders_count ?? 0);
                $paidAmount = (float) ($event->paid_orders_total_amount ?? 0);
                $hasActivePayout = (int) ($event->active_payouts_count ?? 0) > 0;
                $formattedPaidAmount = 'Rp ' . number_format($paidAmount, 0, ',', '.');
            @endphp
            <div class="event-table-row">
                <span class="event-name">{{ $event->title }}</span>
                <span>{{ $event->organizer->org_name }}</span>
                <span>{{ $event->start_date?->translatedFormat('d M Y') ?? '-' }}</span>
                <span>{{ $event->location_name }}</span>
                <span>{{ $event->price > 0 ? 'Rp ' . number_format($event->price, 0, ',', '.') : 'Gratis' }}</span>
                <span class="event-money">
                    <strong>{{ $formattedPaidAmount }}</strong>
                    <small>{{ $paidOrdersCount }} transaksi paid</small>
                </span>
                <span>
                    @if ($hasActivePayout)
                        <span class="status-badge status-pending">Payout aktif</span>
                    @elseif ($paidOrdersCount > 0)
                        <button
                            type="button"
                            class="btn-refund-open"
                            data-refund-url="{{ route('admin.events.refund', $event) }}"
                            data-refund-title="{{ $event->title }}"
                            data-refund-orders="{{ $paidOrdersCount }}"
                            data-refund-amount="{{ $formattedPaidAmount }}"
                        >
                            <x-icon name="refresh" :size="15" />
                            Simulasi Refund
                        </button>
                    @else
                        <span class="muted-text">-</span>
                    @endif
                </span>
            </div>
        @empty
            <div class="empty-card">
                <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
                <p>Belum ada event yang disetujui.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- REJECTED --}}
<div class="tab-content" id="tab-rejected">
    @forelse ($rejectedEvents as $event)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $event->title }}</h3>
                    <span class="event-card-by">oleh {{ $event->organizer->org_name }}</span>
                </div>
                <span class="status-badge status-rejected">Ditolak</span>
            </div>
            @if ($event->reject_reason)
                <div class="reject-reason">
                    <strong>Alasan:</strong> {{ $event->reject_reason }}
                </div>
            @endif
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
            <p>Tidak ada event yang ditolak.</p>
        </div>
    @endforelse
</div>

<div id="refund-modal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3 id="refund-modal-title">Simulasi Refund</h3>
        <p id="refund-modal-desc" class="modal-desc"></p>

        <form id="refund-form" method="POST">
            @csrf
            <div class="form-group">
                <label>Alasan refund</label>
                <textarea name="reason" rows="3" maxlength="500" placeholder="Contoh: Event dibatalkan oleh admin setelah verifikasi." required></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" id="refund-modal-cancel" class="btn-cancel">Batal</button>
                <button type="submit" class="btn-refund-confirm">Jalankan Refund</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab, .tab-content').forEach(el => el.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
        });
    });

    const refundModal = document.getElementById('refund-modal');
    const refundForm = document.getElementById('refund-form');
    const refundTitle = document.getElementById('refund-modal-title');
    const refundDesc = document.getElementById('refund-modal-desc');

    document.querySelectorAll('[data-refund-url]').forEach(button => {
        button.addEventListener('click', () => {
            refundForm.action = button.dataset.refundUrl;
            refundTitle.textContent = `Simulasi Refund "${button.dataset.refundTitle}"`;
            refundDesc.textContent = `${button.dataset.refundOrders} transaksi paid senilai ${button.dataset.refundAmount} akan ditandai refunded dan semua tiketnya dibatalkan.`;
            refundModal.style.display = 'flex';
        });
    });

    document.getElementById('refund-modal-cancel').addEventListener('click', () => {
        refundModal.style.display = 'none';
    });

    refundModal.addEventListener('click', (event) => {
        if (event.target === event.currentTarget) {
            refundModal.style.display = 'none';
        }
    });
</script>
@endpush
