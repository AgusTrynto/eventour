@props([
    'active' => 'dashboard',
    'organizer' => null,
])

@php
    $eoNavbarOrganizer = $organizer ?? auth()->user()?->eventOrganizer;

    $navItems = [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'href' => route('eo.dashboard'),
            'icon' => 'bar-chart',
        ],
        [
            'key' => 'events',
            'label' => 'Event Saya',
            'href' => route('eo.events.index'),
            'icon' => 'calendar',
        ],
        [
            'key' => 'payouts',
            'label' => 'Payout',
            'href' => route('eo.payouts.index'),
            'icon' => 'briefcase',
        ],
        [
            'key' => 'customers',
            'label' => 'Pembeli',
            'href' => route('eo.customers.index'),
            'icon' => 'users',
        ],
        [
            'key' => 'create',
            'label' => 'Tambah Event',
            'href' => route('eo.events.create'),
            'icon' => 'circle-plus',
        ],
        [
            'key' => 'scan',
            'label' => 'Scan',
            'href' => route('eo.scan'),
            'icon' => 'camera',
        ],
    ];

    if ($active === 'reviews') {
        $navItems[] = [
            'key' => 'reviews',
            'label' => 'Ulasan Event',
            'href' => url()->current(),
            'icon' => 'message-circle',
        ];
    }
@endphp

<header class="navbar" data-eo-navbar>
    <div class="container-custom">
        <button type="button" class="hamburger-btn" id="hamburger-btn" aria-label="Buka menu">
            <span></span><span></span><span></span>
        </button>

        <a href="{{ route('dashboard') }}" class="logo">Even<span>Tour</span></a>

        <nav class="nav-links" data-nav-links>
            <span class="nav-active-indicator" aria-hidden="true"></span>
            @foreach ($navItems as $item)
                <a href="{{ $item['href'] }}"
                    class="nav-link {{ $active === $item['key'] ? 'active' : '' }}"
                    data-nav-item="{{ $item['key'] }}">
                    <x-icon name="{{ $item['icon'] }}" :size="15" />
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="nav-right">
            @if ($eoNavbarOrganizer)
                <span class="user-name">{{ $eoNavbarOrganizer->org_name }}</span>
            @endif
            <span class="role-badge">EO</span>

            <form action="{{ route('logout') }}" method="POST" data-no-page-loader>
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>
</header>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<aside class="mobile-sidebar" id="mobile-sidebar">
    <div class="sidebar-top">
        <a href="{{ route('dashboard') }}" class="logo">Even<span>Tour</span></a>
        <button type="button" class="sidebar-close" id="sidebar-close" aria-label="Tutup menu">
            <x-icon name="x" :size="16" />
        </button>
    </div>

    <div class="sidebar-user">
        @if ($eoNavbarOrganizer)
            <span class="user-name">{{ $eoNavbarOrganizer->org_name }}</span>
        @endif
        <span class="role-badge">EO</span>
    </div>

    <nav class="sidebar-nav">
        @foreach ($navItems as $item)
            <a href="{{ $item['href'] }}" class="sidebar-link {{ $active === $item['key'] ? 'active' : '' }}">
                <x-icon name="{{ $item['icon'] }}" :size="18" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <form action="{{ route('logout') }}" method="POST" class="sidebar-logout" data-no-page-loader>
        @csrf
        <button type="submit" class="btn-logout">Logout</button>
    </form>
</aside>

<div class="eo-page-loader" data-page-loader aria-hidden="true">
    <div class="loader-bar" aria-hidden="true"></div>
</div>

<script>
    (() => {
        const body = document.body;
        const navbar = document.querySelector('[data-eo-navbar]');
        const navLinks = document.querySelector('[data-nav-links]');
        const indicator = navLinks?.querySelector('.nav-active-indicator');
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const sidebarClose = document.getElementById('sidebar-close');
        const mobileSidebar = document.getElementById('mobile-sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const pageLoader = document.querySelector('[data-page-loader]');

        function moveIndicator(target) {
            if (!navLinks || !indicator || !target) return;

            const navRect = navLinks.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();

            indicator.style.width = `${targetRect.width}px`;
            indicator.style.transform = `translateX(${targetRect.left - navRect.left}px)`;
            indicator.style.opacity = '1';
        }

        const activeLink = navLinks?.querySelector('.nav-link.active') ?? navLinks?.querySelector('.nav-link');
        requestAnimationFrame(() => moveIndicator(activeLink));

        navLinks?.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('mouseenter', () => moveIndicator(link));
            link.addEventListener('focus', () => moveIndicator(link));
            link.addEventListener('mouseleave', () => moveIndicator(navLinks.querySelector('.nav-link.active') ?? activeLink));
            link.addEventListener('click', () => moveIndicator(link));
        });

        window.addEventListener('resize', () => {
            moveIndicator(navLinks?.querySelector('.nav-link.active') ?? activeLink);
        });

        function openSidebar() {
            mobileSidebar?.classList.add('open');
            sidebarOverlay?.classList.add('open');
            body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            mobileSidebar?.classList.remove('open');
            sidebarOverlay?.classList.remove('open');
            body.style.overflow = '';
        }

        hamburgerBtn?.addEventListener('click', openSidebar);
        sidebarClose?.addEventListener('click', closeSidebar);
        sidebarOverlay?.addEventListener('click', closeSidebar);
        mobileSidebar?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeSidebar);
        });

        function showPageLoader(link = null) {
            body.classList.add('is-page-loading');
            navbar?.classList.add('is-loading');
            pageLoader?.setAttribute('aria-hidden', 'false');

            if (link?.classList.contains('nav-link')) {
                navLinks.querySelectorAll('.nav-link').forEach(item => item.classList.remove('active'));
                link.classList.add('active');
                moveIndicator(link);
            }

            if (link) {
                mobileSidebar?.querySelectorAll('.sidebar-link').forEach(item => {
                    item.classList.toggle('active', item.href === link.href);
                });
            }
        }

        function shouldShowLoaderForLink(link, event) {
            if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
            if (link.target && link.target !== '_self') return false;
            if (link.hasAttribute('download')) return false;

            const href = link.getAttribute('href') ?? '';
            if (!href || href.startsWith('#')) return false;

            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return false;
            if (url.pathname === window.location.pathname && url.hash) return false;

            return true;
        }

        document.addEventListener('click', event => {
            const link = event.target.closest('a[href]');
            if (!link || !shouldShowLoaderForLink(link, event)) return;

            const url = new URL(link.href, window.location.href);
            showPageLoader(link);

            event.preventDefault();
            requestAnimationFrame(() => {
                window.location.assign(url.href);
            });
        });

        document.addEventListener('submit', event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || form.matches('[data-no-page-loader]')) return;
            showPageLoader();
        });

        window.addEventListener('pageshow', () => {
            body.classList.remove('is-page-loading');
            navbar?.classList.remove('is-loading');
            pageLoader?.setAttribute('aria-hidden', 'true');
        });
    })();
</script>
