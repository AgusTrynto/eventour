@extends('admin.layout')

@section('title', 'Manajemen EO')
@section('page-title', 'Akun Event Organizer')

@section('content')

{{-- TABS --}}
<div class="tabs">
    <button class="tab active" data-tab="pending">
        Pending <span class="tab-count">{{ $pendingEO->count() }}</span>
    </button>
    <button class="tab" data-tab="approved">
        Disetujui <span class="tab-count approved">{{ $approvedEO->count() }}</span>
    </button>
    <button class="tab" data-tab="rejected">
        Ditolak <span class="tab-count rejected">{{ $rejectedEO->count() }}</span>
    </button>
</div>

{{-- PENDING --}}
<div class="tab-content active" id="tab-pending">
    @forelse ($pendingEO as $eo)
        <div class="card eo-card">
            <div class="eo-header">
                <div class="eo-avatar">{{ strtoupper(substr($eo->org_name, 0, 1)) }}</div>
                <div class="eo-info">
                    <h3>{{ $eo->org_name }}</h3>
                    <span>{{ $eo->user->email }}</span>
                </div>
                <span class="status-badge status-pending">Pending</span>
            </div>

            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Penanggung Jawab</span>
                    <span>{{ $eo->user->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Telepon</span>
                    <span>{{ $eo->phone }}</span>
                </div>
                @if ($eo->address)
                    <div class="detail-row">
                        <span class="detail-label">Alamat</span>
                        <span>{{ $eo->address }}</span>
                    </div>
                @endif
                <div class="detail-row">
                    <span class="detail-label">Daftar</span>
                    <span>{{ $eo->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
            </div>

            <div class="eo-actions">
                <form action="{{ route('admin.eo.approve', $eo) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-approve-full">
                        <x-icon name="check-circle" :size="17" />
                        Setujui
                    </button>
                </form>
                <button type="button" class="btn-reject-full"
                    onclick="openRejectModal({{ $eo->id }}, '{{ addslashes($eo->org_name) }}', 'eo')">
                    <x-icon name="x" :size="17" />
                    Tolak
                </button>
            </div>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="check-circle" :size="38" /></span>
            <p>Tidak ada EO yang menunggu persetujuan.</p>
        </div>
    @endforelse
</div>

{{-- APPROVED --}}
<div class="tab-content" id="tab-approved">
    @forelse ($approvedEO as $eo)
        <div class="card eo-card">
            <div class="eo-header">
                <div class="eo-avatar approved">{{ strtoupper(substr($eo->org_name, 0, 1)) }}</div>
                <div class="eo-info">
                    <h3>{{ $eo->org_name }}</h3>
                    <span>{{ $eo->user->email }}</span>
                </div>
                <span class="status-badge status-approved">Disetujui</span>
            </div>
            <div class="eo-details">
                <div class="detail-row">
                    <span class="detail-label">Penanggung Jawab</span>
                    <span>{{ $eo->user->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Telepon</span>
                    <span>{{ $eo->phone }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Event</span>
                    <span>{{ $eo->events->count() }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
            <p>Belum ada EO yang disetujui.</p>
        </div>
    @endforelse
</div>

{{-- REJECTED --}}
<div class="tab-content" id="tab-rejected">
    @forelse ($rejectedEO as $eo)
        <div class="card eo-card">
            <div class="eo-header">
                <div class="eo-avatar rejected">{{ strtoupper(substr($eo->org_name, 0, 1)) }}</div>
                <div class="eo-info">
                    <h3>{{ $eo->org_name }}</h3>
                    <span>{{ $eo->user->email }}</span>
                </div>
                <span class="status-badge status-rejected">Ditolak</span>
            </div>
            @if ($eo->reject_reason)
                <div class="reject-reason">
                    <strong>Alasan:</strong> {{ $eo->reject_reason }}
                </div>
            @endif
        </div>
    @empty
        <div class="empty-card">
            <span class="empty-card-icon"><x-icon name="inbox" :size="38" /></span>
            <p>Tidak ada EO yang ditolak.</p>
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
