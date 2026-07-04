@extends('admin.layout')

@section('title', 'Payout')
@section('page-title', 'Payout EO')

@section('content')

<div class="tabs">
    <button class="tab active" data-tab="ready">
        Siap Cair <span class="tab-count">{{ $readyForPayout->count() }}</span>
    </button>
    <button class="tab" data-tab="processing">
        Diproses <span class="tab-count">{{ $processingPayouts->count() }}</span>
    </button>
    <button class="tab" data-tab="completed">
        Selesai <span class="tab-count approved">{{ $completedPayouts->count() }}</span>
    </button>
</div>

<div class="tab-content active" id="tab-ready">
    @forelse ($readyForPayout as $event)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $event->title }}</h3>
                    <span class="event-card-by">oleh {{ $event->organizer->org_name }}</span>
                </div>
                <span class="status-badge status-pending">Siap dicairkan</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Tanggal selesai</span>
                    <span>{{ $event->end_date?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Dana escrow</span>
                    <span>Rp {{ number_format($event->escrow_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tiket terjual</span>
                    <span>{{ $event->tickets_sold }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Rekening EO</span>
                    <span>
                        {{ $event->organizer->bank_name ?? '-' }}
                        {{ $event->organizer->bank_account_number ? ' - ' . $event->organizer->bank_account_number : '' }}
                        {{ $event->organizer->bank_account_name ? ' a.n. ' . $event->organizer->bank_account_name : '' }}
                    </span>
                </div>
            </div>

            <div class="eo-actions">
                <form action="{{ route('admin.payouts.create', $event) }}" method="POST" class="payout-action">
                    @csrf
                    <button type="submit" class="btn-approve-full">
                        <x-icon name="check-circle" :size="17" />
                        Buat Payout
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="check-circle" :size="38" /></span>
            <p>Belum ada event yang siap dicairkan.</p>
        </div>
    @endforelse
</div>

<div class="tab-content" id="tab-processing">
    @forelse ($processingPayouts as $payout)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $payout->event->title ?? 'Event tidak ditemukan' }}</h3>
                    <span class="event-card-by">oleh {{ $payout->organizer->org_name ?? '-' }}</span>
                </div>
                <span class="status-badge status-pending">{{ ucfirst($payout->status) }}</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Gross</span>
                    <span>Rp {{ number_format($payout->gross_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fee platform</span>
                    <span>Rp {{ number_format($payout->platform_fee, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Net transfer</span>
                    <span>Rp {{ number_format($payout->net_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Rekening EO</span>
                    <span>
                        {{ $payout->organizer->bank_name ?? '-' }}
                        {{ $payout->organizer->bank_account_number ? ' - ' . $payout->organizer->bank_account_number : '' }}
                        {{ $payout->organizer->bank_account_name ? ' a.n. ' . $payout->organizer->bank_account_name : '' }}
                    </span>
                </div>
            </div>

            <form action="{{ route('admin.payouts.complete', $payout) }}" method="POST" enctype="multipart/form-data" class="payout-form">
                @csrf
                <div class="payout-form-grid">
                    <label>
                        Bukti transfer
                        <input type="file" name="transfer_proof" accept="image/*" required>
                    </label>
                    <label>
                        Catatan admin
                        <textarea name="admin_note" rows="3" maxlength="500" placeholder="Opsional"></textarea>
                    </label>
                </div>
                <button type="submit" class="btn-approve-full">
                    <x-icon name="check-circle" :size="17" />
                    Tandai Sudah Transfer
                </button>
            </form>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
            <p>Tidak ada payout yang sedang diproses.</p>
        </div>
    @endforelse
</div>

<div class="tab-content" id="tab-completed">
    @forelse ($completedPayouts as $payout)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $payout->event->title ?? 'Event tidak ditemukan' }}</h3>
                    <span class="event-card-by">oleh {{ $payout->organizer->org_name ?? '-' }}</span>
                </div>
                <span class="status-badge status-approved">Selesai</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Net transfer</span>
                    <span>Rp {{ number_format($payout->net_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Diproses</span>
                    <span>{{ $payout->processed_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                </div>
                @if ($payout->admin_note)
                    <div class="detail-row full">
                        <span class="detail-label">Catatan</span>
                        <p class="detail-desc">{{ $payout->admin_note }}</p>
                    </div>
                @endif
                @if ($payout->transfer_proof)
                    <div class="detail-row">
                        <span class="detail-label">Bukti transfer</span>
                        <a class="card-link" href="{{ asset('storage/' . $payout->transfer_proof) }}" target="_blank" rel="noopener">
                            Lihat bukti
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
            <p>Belum ada payout yang selesai.</p>
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
