@extends('admin.layout')

@section('title', 'Refund Manual')
@section('page-title', 'Refund Manual')

@section('content')

<div class="tabs">
    <button class="tab active" data-tab="awaiting">
        Menunggu Data User <span class="tab-count">{{ $awaitingDestination->count() }}</span>
    </button>
    <button class="tab" data-tab="ready">
        Siap Transfer <span class="tab-count approved">{{ $readyToTransfer->count() }}</span>
    </button>
    <button class="tab" data-tab="completed">
        Selesai <span class="tab-count">{{ $completedManualRefunds->count() }}</span>
    </button>
</div>

<div class="tab-content active" id="tab-awaiting">
    @forelse ($awaitingDestination as $order)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $order->event->title ?? 'Event tidak ditemukan' }}</h3>
                    <span class="event-card-by">Order #{{ $order->id }} oleh {{ $order->user->name ?? '-' }}</span>
                </div>
                <span class="status-badge status-pending">Menunggu data</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Email user</span>
                    <span>{{ $order->user->email ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nilai refund</span>
                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Diminta</span>
                    <span>{{ $order->refund_requested_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                </div>
                <div class="detail-row full">
                    <span class="detail-label">Alasan</span>
                    <p class="detail-desc">{{ $order->refund_reason ?? '-' }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="check-circle" :size="38" /></span>
            <p>Tidak ada refund yang menunggu data user.</p>
        </div>
    @endforelse
</div>

<div class="tab-content" id="tab-ready">
    @forelse ($readyToTransfer as $order)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $order->event->title ?? 'Event tidak ditemukan' }}</h3>
                    <span class="event-card-by">Order #{{ $order->id }} oleh {{ $order->user->name ?? '-' }}</span>
                </div>
                <span class="status-badge status-pending">Siap transfer</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Nilai refund</span>
                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tujuan</span>
                    <span>
                        {{ $order->refund_destination_type === 'ewallet' ? 'E-wallet' : 'Bank' }}
                        {{ $order->refund_destination_provider ? ' - ' . $order->refund_destination_provider : '' }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Nomor</span>
                    <span>{{ $order->refund_destination_account_number ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Atas nama</span>
                    <span>{{ $order->refund_destination_account_name ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Dikirim user</span>
                    <span>{{ $order->refund_destination_submitted_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                </div>
            </div>

            <form action="{{ route('admin.refunds.complete', $order) }}" method="POST" enctype="multipart/form-data" class="payout-form">
                @csrf
                <div class="payout-form-grid">
                    <label>
                        Bukti transfer refund
                        <input type="file" name="manual_refund_proof" accept="image/*" required>
                    </label>
                    <label>
                        Catatan admin
                        <textarea name="manual_refund_admin_note" rows="3" maxlength="500" placeholder="Opsional"></textarea>
                    </label>
                </div>
                <button type="submit" class="btn-approve-full">
                    <x-icon name="check-circle" :size="17" />
                    Tandai Refund Selesai
                </button>
            </form>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
            <p>Belum ada refund manual yang siap ditransfer.</p>
        </div>
    @endforelse
</div>

<div class="tab-content" id="tab-completed">
    @forelse ($completedManualRefunds as $order)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $order->event->title ?? 'Event tidak ditemukan' }}</h3>
                    <span class="event-card-by">Order #{{ $order->id }} oleh {{ $order->user->name ?? '-' }}</span>
                </div>
                <span class="status-badge status-approved">Selesai</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Nilai refund</span>
                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tujuan</span>
                    <span>
                        {{ $order->refund_destination_provider ?? '-' }}
                        {{ $order->refund_destination_account_number ? ' - ' . $order->refund_destination_account_number : '' }}
                        {{ $order->refund_destination_account_name ? ' a.n. ' . $order->refund_destination_account_name : '' }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Selesai</span>
                    <span>{{ $order->manual_refunded_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                </div>
                @if ($order->manual_refund_proof)
                    <div class="detail-row">
                        <span class="detail-label">Bukti transfer</span>
                        <a class="card-link" href="{{ asset('storage/' . $order->manual_refund_proof) }}" target="_blank" rel="noopener">
                            Lihat bukti
                        </a>
                    </div>
                @endif
                @if ($order->manual_refund_admin_note)
                    <div class="detail-row full">
                        <span class="detail-label">Catatan</span>
                        <p class="detail-desc">{{ $order->manual_refund_admin_note }}</p>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
            <p>Belum ada refund manual yang selesai.</p>
        </div>
    @endforelse
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
</script>
@endpush
