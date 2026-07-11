<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - EvenTour</title>
    @vite(['resources/css/admin/admin.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="admin-page">
    <div class="bg-glow"></div>

    {{-- SIDEBAR --}}
    <aside class="sidebar">
        <div class="sidebar-top">
            <a href="{{ route('admin.dashboard') }}" class="logo">Even<span>Tour</span></a>
            <span class="admin-badge">ADMIN</span>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <x-icon name="bar-chart" :size="18" />
                Dashboard
            </a>

            <span class="sidebar-section">Manajemen</span>

            <a href="{{ route('admin.eo.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.eo.*') ? 'active' : '' }}">
                <x-icon name="building" :size="18" />
                Akun EO
                @if ($pendingSidebar['eo'] > 0)
                    <span class="nav-badge">{{ $pendingSidebar['eo'] }}</span>
                @endif
            </a>

            <a href="{{ route('admin.events.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                <x-icon name="ticket" :size="18" />
                Event
                @if ($pendingSidebar['events'] > 0)
                    <span class="nav-badge">{{ $pendingSidebar['events'] }}</span>
                @endif
            </a>

            <a href="{{ route('admin.payouts.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.payouts.*') ? 'active' : '' }}">
                <x-icon name="briefcase" :size="18" />
                Payout
            </a>

            <a href="{{ route('admin.refunds.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
                <x-icon name="refresh" :size="18" />
                Refund Manual
                @if (($pendingSidebar['refunds'] ?? 0) > 0)
                    <span class="nav-badge">{{ $pendingSidebar['refunds'] }}</span>
                @endif
            </a>
        </nav>

        <form action="{{ route('logout') }}" method="POST" class="sidebar-bottom">
            @csrf
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </aside>

    {{-- CONTENT --}}
    <div class="admin-main">
        <header class="admin-header">
            <h1 class="page-title">@yield('page-title')</h1>
            <span class="header-user">{{ Auth::user()->name }}</span>
        </header>

        <main class="admin-content">
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

            @yield('content')
        </main>
    </div>

    {{-- MODAL REJECT --}}
    <div id="reject-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <h3 id="modal-title">Tolak Permintaan</h3>
            <p id="modal-desc" class="modal-desc"></p>

            <form id="reject-form" method="POST">
                @csrf
                <div class="form-group">
                    <label>Alasan penolakan (opsional)</label>
                    <textarea name="reason" rows="3" placeholder="Jelaskan alasan penolakan..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" id="modal-cancel" class="btn-cancel">Batal</button>
                    <button type="submit" class="btn-reject-confirm">Tolak</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(id, name, type) {
            const routes = {
                eo:    `/admin/eo/${id}/reject`,
                event: `/admin/events/${id}/reject`,
            };
            document.getElementById('modal-title').textContent = `Tolak "${name}"`;
            document.getElementById('modal-desc').textContent =
                type === 'eo'
                    ? 'Akun EO ini akan ditolak dan tidak bisa membuat event.'
                    : 'Event ini akan ditolak dan tidak tampil di map.';
            document.getElementById('reject-form').action = routes[type];
            document.getElementById('reject-modal').style.display = 'flex';
        }

        document.getElementById('modal-cancel').addEventListener('click', () => {
            document.getElementById('reject-modal').style.display = 'none';
        });

        document.getElementById('reject-modal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                e.currentTarget.style.display = 'none';
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
