@extends('admin.layout')

@section('title', 'Payout')
@section('page-title', 'Payout EO')

@section('content')

<div class="tabs">
    <button class="tab active" data-tab="ready">
        Pengajuan EO <span class="tab-count">{{ $pendingPayouts->count() }}</span>
    </button>
    <button class="tab" data-tab="processing">
        Diproses <span class="tab-count">{{ $processingPayouts->count() }}</span>
    </button>
    <button class="tab" data-tab="failed">
        Gagal <span class="tab-count">{{ $failedPayouts->count() }}</span>
    </button>
    <button class="tab" data-tab="completed">
        Selesai <span class="tab-count approved">{{ $completedPayouts->count() }}</span>
    </button>
    <button class="tab" data-tab="rejected">
        Ditolak <span class="tab-count">{{ $rejectedPayouts->count() }}</span>
    </button>
</div>

<div class="tab-content active" id="tab-ready">
    @forelse ($pendingPayouts as $payout)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $payout->event->title ?? 'Event tidak ditemukan' }}</h3>
                    <span class="event-card-by">oleh {{ $payout->organizer->org_name ?? '-' }}</span>
                </div>
                <span class="status-badge status-pending">Menunggu review</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Diajukan</span>
                    <span>{{ $payout->requested_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Dana tertahan</span>
                    <span>Rp {{ number_format($payout->gross_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Net transfer</span>
                    <span>Rp {{ number_format($payout->net_amount, 0, ',', '.') }}</span>
                </div>
                @if ($payout->request_reason)
                    <div class="detail-row full">
                        <span class="detail-label">Alasan EO</span>
                        <p class="detail-desc">{{ $payout->request_reason }}</p>
                    </div>
                @endif
                @if ($payout->request_attachment)
                    <div class="detail-row">
                        <span class="detail-label">Foto pendukung</span>
                        <a class="card-link" href="{{ asset('storage/' . $payout->request_attachment) }}" target="_blank" rel="noopener">
                            Lihat foto
                        </a>
                    </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Rekening EO</span>
                    <span>
                        {{ $payout->organizer->bank_name ?? '-' }}
                        {{ $payout->organizer->bank_channel_code ? ' (' . $payout->organizer->bank_channel_code . ')' : '' }}
                        {{ $payout->organizer->bank_account_number ? ' - ' . $payout->organizer->bank_account_number : '' }}
                        {{ $payout->organizer->bank_account_name ? ' a.n. ' . $payout->organizer->bank_account_name : '' }}
                    </span>
                </div>
            </div>

            <div class="eo-actions">
                <form action="{{ route('admin.payouts.approve', $payout) }}" method="POST" class="payout-action">
                    @csrf
                    <button type="submit" class="btn-approve-full">
                        <x-icon name="check-circle" :size="17" />
                        Setujui & Kirim Otomatis
                    </button>
                </form>
                <form action="{{ route('admin.payouts.reject', $payout) }}" method="POST" class="payout-form">
                    @csrf
                    <label>
                        Alasan penolakan
                        <textarea name="admin_note" rows="3" maxlength="500" placeholder="Wajib diisi jika ditolak" required></textarea>
                    </label>
                    <button type="submit" class="btn-reject-full">
                        Tolak
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="check-circle" :size="38" /></span>
            <p>Belum ada pengajuan pencairan dari EO.</p>
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
                <span class="status-badge status-pending">{{ $payout->xendit_payout_status ?? ucfirst($payout->status) }}</span>
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
                        {{ $payout->organizer->bank_channel_code ? ' (' . $payout->organizer->bank_channel_code . ')' : '' }}
                        {{ $payout->organizer->bank_account_number ? ' - ' . $payout->organizer->bank_account_number : '' }}
                        {{ $payout->organizer->bank_account_name ? ' a.n. ' . $payout->organizer->bank_account_name : '' }}
                    </span>
                </div>
                @if ($payout->xendit_payout_reference_id)
                    <div class="detail-row">
                        <span class="detail-label">Reference</span>
                        <span>{{ $payout->xendit_payout_reference_id }}</span>
                    </div>
                @endif
                @if ($payout->xendit_payout_id)
                    <div class="detail-row">
                        <span class="detail-label">Xendit Payout ID</span>
                        <span>{{ $payout->xendit_payout_id }}</span>
                    </div>
                @endif
                @if ($payout->request_reason)
                    <div class="detail-row full">
                        <span class="detail-label">Alasan EO</span>
                        <p class="detail-desc">{{ $payout->request_reason }}</p>
                    </div>
                @endif
                @if ($payout->request_attachment)
                    <div class="detail-row">
                        <span class="detail-label">Foto pendukung</span>
                        <a class="card-link" href="{{ asset('storage/' . $payout->request_attachment) }}" target="_blank" rel="noopener">
                            Lihat foto
                        </a>
                    </div>
                @endif
            </div>

            @if ($payout->xendit_payout_reference_id)
                <div class="payout-form">
                    <p class="detail-desc">Auto payout sedang diproses oleh Xendit. Status akan berubah saat webhook sukses masuk.</p>
                </div>
            @else
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
            @endif
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
            <p>Tidak ada payout yang sedang diproses.</p>
        </div>
    @endforelse
</div>

<div class="tab-content" id="tab-failed">
    @forelse ($failedPayouts as $payout)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $payout->event->title ?? 'Event tidak ditemukan' }}</h3>
                    <span class="event-card-by">oleh {{ $payout->organizer->org_name ?? '-' }}</span>
                </div>
                <span class="status-badge status-rejected">Payout gagal</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Net transfer</span>
                    <span>Rp {{ number_format($payout->net_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Rekening EO</span>
                    <span>
                        {{ $payout->organizer->bank_name ?? '-' }}
                        {{ $payout->organizer->bank_channel_code ? ' (' . $payout->organizer->bank_channel_code . ')' : '' }}
                        {{ $payout->organizer->bank_account_number ? ' - ' . $payout->organizer->bank_account_number : '' }}
                        {{ $payout->organizer->bank_account_name ? ' a.n. ' . $payout->organizer->bank_account_name : '' }}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Failure code</span>
                    <span>{{ $payout->xendit_payout_failure_code ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Reference</span>
                    <span>{{ $payout->xendit_payout_reference_id ?? '-' }}</span>
                </div>
            </div>

            <form action="{{ route('admin.payouts.retry', $payout) }}" method="POST" class="payout-action">
                @csrf
                <button type="submit" class="btn-approve-full">
                    <x-icon name="refresh" :size="17" />
                    Retry Auto Payout
                </button>
            </form>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="check-circle" :size="38" /></span>
            <p>Tidak ada payout EO yang gagal.</p>
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
                <div class="detail-row">
                    <span class="detail-label">Metode</span>
                    <span>{{ $payout->xendit_payout_id ? 'Xendit Payout' : 'Manual' }}</span>
                </div>
                @if ($payout->xendit_payout_reference_id)
                    <div class="detail-row">
                        <span class="detail-label">Reference</span>
                        <span>{{ $payout->xendit_payout_reference_id }}</span>
                    </div>
                @endif
                @if ($payout->xendit_payout_id)
                    <div class="detail-row">
                        <span class="detail-label">Xendit Payout ID</span>
                        <span>{{ $payout->xendit_payout_id }}</span>
                    </div>
                @endif
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

<div class="tab-content" id="tab-rejected">
    @forelse ($rejectedPayouts as $payout)
        <div class="card event-card">
            <div class="event-card-header">
                <div class="event-card-info">
                    <h3>{{ $payout->event->title ?? 'Event tidak ditemukan' }}</h3>
                    <span class="event-card-by">oleh {{ $payout->organizer->org_name ?? '-' }}</span>
                </div>
                <span class="status-badge status-rejected">Ditolak</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Dana diajukan</span>
                    <span>Rp {{ number_format($payout->gross_amount, 0, ',', '.') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Direview</span>
                    <span>{{ $payout->reviewed_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                </div>
                @if ($payout->request_reason)
                    <div class="detail-row full">
                        <span class="detail-label">Alasan EO</span>
                        <p class="detail-desc">{{ $payout->request_reason }}</p>
                    </div>
                @endif
                @if ($payout->admin_note)
                    <div class="detail-row full">
                        <span class="detail-label">Alasan admin</span>
                        <p class="detail-desc">{{ $payout->admin_note }}</p>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
            <p>Belum ada pengajuan yang ditolak.</p>
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
