<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Saya - EvenTour</title>
    @vite(['resources/css/tickets/tickets.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="ticket-list-page">

    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'tickets'])

    <main class="main-content">
        <div class="container-custom">

            <div class="page-heading">
                <div class="page-heading-inner">
                    <span class="badge">TIKET SAYA</span>
                    <h1>Riwayat Tiket</h1>
                </div>
                <p>Pilih pemegang tiket untuk melihat semua QR atas nama orang tersebut.</p>
            </div>

            @if (session('success'))
                <div class="ticket-alert ticket-alert-success">
                    <x-icon name="check-circle" :size="18" />
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="ticket-alert ticket-alert-error">
                    <x-icon name="alert-triangle" :size="18" />
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="ticket-alert ticket-alert-error">
                    <x-icon name="alert-triangle" :size="18" />
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form class="ticket-toolbar" method="GET" action="{{ route('tickets.index') }}">
                <div class="toolbar-filters">
                    <div class="search-input-wrap">
                        <x-icon name="search" :size="16" />
                        <input
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama tiket atau pemegang tiket..."
                            autocomplete="off"
                            aria-label="Cari tiket"
                        >
                        @if(request('search'))
                            <button type="button" class="search-clear-btn" aria-label="Bersihkan pencarian" onclick="this.form.search.value='';this.form.submit();">
                                <x-icon name="x" :size="14" />
                            </button>
                        @endif
                    </div>

                    <select name="status" aria-label="Filter status" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="valid" {{ request('status') === 'valid' ? 'selected' : '' }}>Aktif</option>
                        <option value="used" {{ request('status') === 'used' ? 'selected' : '' }}>Terpakai</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Batal</option>
                    </select>

                    <select name="sort" aria-label="Urutkan" onchange="this.form.submit()">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Pembelian Terbaru</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Pembelian Terlama</option>
                        <option value="event_upcoming" {{ request('sort') === 'event_upcoming' ? 'selected' : '' }}>Event Akan Datang</option>
                        <option value="event_past" {{ request('sort') === 'event_past' ? 'selected' : '' }}>Event Sudah Lewat</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama Event A-Z</option>
                    </select>
                </div>

                <div class="toolbar-meta">
                    <span class="result-count">{{ $paginator->total() }} pemegang tiket</span>
                    @if(request()->has('search') || request()->has('status') || request()->has('sort'))
                        <a href="{{ route('tickets.index') }}" class="reset-link">Reset filter</a>
                    @endif
                </div>
            </form>

            <div class="ticket-holder-list">
                @forelse ($ticketGroups as $ticketGroup)
                    @php
                        $ticket = $ticketGroup->ticket;
                        $holderName = $ticketGroup->holder_name;
                    @endphp

                    <a href="{{ route('tickets.show', $ticket) }}"
                        class="ticket-holder-card"
                        aria-label="Buka QR tiket {{ $ticket->event->title }} atas nama {{ $holderName }}">
                        <div class="ticket-holder-main">
                            <div>
                                <span class="ticket-holder-label">Nama Tiket</span>
                                <h2>{{ $ticket->event->title }}</h2>
                            </div>

                            <div>
                                <span class="ticket-holder-label">Pemegang Tiket</span>
                                <p>{{ $holderName }}</p>
                            </div>
                        </div>

                        <span class="ticket-holder-open" aria-hidden="true">
                            <x-icon name="arrow-right" :size="18" />
                        </span>
                    </a>
                @empty
                    <div class="empty-state">
                        <span class="empty-state-icon"><x-icon name="ticket" :size="38" /></span>
                        <p>Kamu belum punya tiket apapun.</p>
                        <a href="{{ route('dashboard') }}" class="btn-explore">Jelajahi Event</a>
                    </div>
                @endforelse
            </div>

            @if ($paginator->hasPages())
                <div class="pagination-wrap">
                    {{ $paginator->links('templates.pagination') }}
                </div>
            @endif

        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>

</body>
</html>
