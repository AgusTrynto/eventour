@props([
    'active' => 'dashboard',
    'user' => null,
])

@php
    $navbarUser = $user ?? auth()->user();

    $navItems = [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'href' => route('dashboard'),
            'icon' => 'bar-chart',
        ],
        [
            'key' => 'events',
            'label' => 'Event',
            'href' => route('dashboard') . '#event-map',
            'icon' => 'calendar',
        ],
        [
            'key' => 'tickets',
            'label' => 'Tiket Saya',
            'href' => route('tickets.index'),
            'icon' => 'ticket',
        ],
        [
            'key' => 'reviews',
            'label' => 'Ulasan',
            'href' => route('reviews.index'),
            'icon' => 'star',
        ],
    ];
@endphp

<header class="navbar user-navbar" data-user-navbar>
    <div class="container-custom">
        <button type="button" class="hamburger-btn" id="hamburger-btn" aria-label="Buka menu">
            <span></span><span></span><span></span>
        </button>

        <a href="{{ route('dashboard') }}" class="logo">Even<span>Tour</span></a>

        <nav class="nav-links" data-user-nav-links>
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
            @if ($navbarUser?->role === 'eo')
                <a href="{{ route('eo.dashboard') }}" class="nav-link-eo-badge">
                    <x-icon name="building" :size="15" />
                    Dashboard EO
                </a>
            @endif

            @if ($navbarUser)
                <span class="user-name">{{ $navbarUser->name }}</span>
            @endif

            <form action="{{ route('logout') }}" method="POST" data-no-page-loader>
                @csrf
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </div>
    </div>
</header>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<aside class="mobile-sidebar user-sidebar" id="mobile-sidebar">
    <div class="sidebar-top">
        <a href="{{ route('dashboard') }}" class="logo">Even<span>Tour</span></a>
        <button type="button" class="sidebar-close" id="sidebar-close" aria-label="Tutup menu">
            <x-icon name="x" :size="16" />
        </button>
    </div>

    @if ($navbarUser)
        <div class="sidebar-user">
            <span class="user-name">{{ $navbarUser->name }}</span>
        </div>
    @endif

    <nav class="sidebar-nav">
        @foreach ($navItems as $item)
            <a href="{{ $item['href'] }}"
                class="sidebar-link {{ $active === $item['key'] ? 'active' : '' }}"
                data-nav-item="{{ $item['key'] }}">
                <x-icon name="{{ $item['icon'] }}" :size="18" />
                {{ $item['label'] }}
            </a>
        @endforeach

        @if ($navbarUser?->role === 'eo')
            <a href="{{ route('eo.dashboard') }}" class="sidebar-link sidebar-link-eo">
                <x-icon name="building" :size="18" />
                Dashboard EO
            </a>
        @endif
    </nav>

    <form action="{{ route('logout') }}" method="POST" class="sidebar-logout" data-no-page-loader>
        @csrf
        <button type="submit" class="btn-logout">Logout</button>
    </form>
</aside>

<div class="user-page-loader" data-page-loader aria-hidden="true">
    <div class="loader-spinner" aria-hidden="true"></div>
    <div class="loader-bar" aria-hidden="true"></div>
</div>

<script>
    (() => {
        const body = document.body;
        const navbar = document.querySelector('[data-user-navbar]');
        const navLinks = document.querySelector('[data-user-nav-links]');
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

        function setActiveNav(link) {
            if (!link) return;

            const key = link.dataset.navItem;
            navLinks?.querySelectorAll('.nav-link').forEach(item => {
                item.classList.toggle('active', item.dataset.navItem === key);
            });
            mobileSidebar?.querySelectorAll('.sidebar-link[data-nav-item]').forEach(item => {
                item.classList.toggle('active', item.dataset.navItem === key);
            });

            const desktopLink = navLinks?.querySelector(`.nav-link[data-nav-item="${key}"]`);
            moveIndicator(desktopLink ?? link);
        }

        function isSamePageHashLink(link) {
            const href = link.getAttribute('href') ?? '';
            if (!href) return false;

            const url = new URL(link.href, window.location.href);
            return url.origin === window.location.origin
                && url.pathname === window.location.pathname
                && url.search === window.location.search
                && Boolean(url.hash);
        }

        const initialLink = window.location.hash === '#event-map'
            ? navLinks?.querySelector('.nav-link[data-nav-item="events"]')
            : navLinks?.querySelector('.nav-link.active') ?? navLinks?.querySelector('.nav-link');

        if (window.location.hash === '#event-map' && initialLink) {
            setActiveNav(initialLink);
        }

        requestAnimationFrame(() => moveIndicator(initialLink));

        navLinks?.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('mouseenter', () => moveIndicator(link));
            link.addEventListener('focus', () => moveIndicator(link));
            link.addEventListener('mouseleave', () => moveIndicator(navLinks.querySelector('.nav-link.active') ?? initialLink));
            link.addEventListener('click', () => {
                if (isSamePageHashLink(link)) {
                    setActiveNav(link);
                } else {
                    moveIndicator(link);
                }
            });
        });

        window.addEventListener('resize', () => {
            moveIndicator(navLinks?.querySelector('.nav-link.active') ?? initialLink);
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
            link.addEventListener('click', () => {
                if (isSamePageHashLink(link)) setActiveNav(link);
                closeSidebar();
            });
        });

        function showPageLoader(link = null) {
            body.classList.add('is-page-loading');
            navbar?.classList.add('is-loading');
            pageLoader?.setAttribute('aria-hidden', 'false');

            if (link?.dataset.navItem) {
                setActiveNav(link);
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
            if (url.pathname === window.location.pathname && url.search === window.location.search) return false;

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
            requestAnimationFrame(() => moveIndicator(navLinks?.querySelector('.nav-link.active') ?? initialLink));
        });
    })();
</script>
