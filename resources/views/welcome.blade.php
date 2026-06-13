<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>EvenTour — Temukan Event di Sekitarmu</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <!-- Laravel Vite -->
    @vite(['resources/css/landing.css', 'resources/js/app.js'])
</head>
<body>

  <!-- ============================
       NAVBAR
  ============================= -->
  <nav class="navbar" id="navbar">
    <a href="#" class="navbar__brand">Even<span>Tour</span></a>

    <button class="navbar__toggle" id="navToggle" aria-label="Toggle menu">☰</button>

    <ul class="navbar__links" id="navLinks">
      <li><a href="#map-section">Peta Event</a></li>
      <li><a href="#event-section">Event</a></li>
      <li><a href="#eo-section">Buat Event</a></li>
      <li><a href="/login" class="btn-ghost">Login</a></li>
      <li><a href="/register" class="btn-accent">Daftar</a></li>
    </ul>
  </nav>

  <!-- ============================
       HERO
  ============================= -->
  <section class="hero" id="home">

    <div class="hero__bg">
      <div class="hero__grid-lines"></div>
      <div class="hero__orb hero__orb--1"></div>
      <div class="hero__orb hero__orb--2"></div>
      <div class="hero__orb hero__orb--3"></div>
    </div>

    <div class="hero__container">

      <!-- Left: Copy -->
      <div class="hero__content">
        <div class="hero__badge">Platform Event Pertama di Indonesia</div>

        <h1 class="hero__title">
          Temukan Event<br>
          <em>Menarik</em> di<br>
          Sekitarmu
        </h1>

        <p class="hero__desc">
          Cari konser, seminar, festival, dan berbagai event lainnya
          langsung melalui peta interaktif real-time.
        </p>

        <div class="hero__actions">
          <a href="#map-section" class="btn-primary">
            🗺 Jelajahi Peta
          </a>
          <a href="#event-section" class="btn-secondary">
            Lihat Semua Event →
          </a>
        </div>

        <div class="hero__stats">
          <div>
            <div class="hero__stat-num">
              <span class="count-num" data-target="2400">0</span>+
            </div>
            <div class="hero__stat-label">Event Aktif</div>
          </div>
          <div>
            <div class="hero__stat-num">
              <span class="count-num" data-target="150">0</span>+
            </div>
            <div class="hero__stat-label">Kota</div>
          </div>
          <div>
            <div class="hero__stat-num">
              <span class="count-num" data-target="98">0</span>k+
            </div>
            <div class="hero__stat-label">Pengguna</div>
          </div>
        </div>
      </div>

      <!-- Right: Visual -->
      <div class="hero__visual">
        <div class="hero__image-wrap">
          <img
            src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=900&q=80"
            alt="Festival crowd"
            loading="eager"
          >
          <div class="hero__image-overlay"></div>
        </div>

        <div class="hero__float-card">
          <div class="hero__float-card-icon">🎟</div>
          <div>
            <div class="hero__float-card-title">Festival Musik Nusantara</div>
            <div class="hero__float-card-sub">Sabtu, 12 Jul · Semarang</div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ============================
       PETA EVENT
  ============================= -->
  <section id="map-section" class="section">
    <div class="container">

      <div class="section-header reveal">
        <div class="section-tag">📍 Lokasi Real-time</div>
        <h2 class="section-title">Peta Event</h2>
        <p class="section-sub">
          Semua event tersebar di peta — klik marker untuk detail dan beli tiket langsung.
        </p>
      </div>

      <div id="map" class="reveal reveal-delay-2"></div>

    </div>
  </section>

  <!-- ============================
       FITUR
  ============================= -->
  <section class="section section--alt">
    <div class="container">

      <div class="reveal">
        <div class="section-tag">✦ Kenapa EventMap</div>
        <h2 class="section-title">Semua yang Kamu Butuhkan</h2>
        <p class="section-sub">Dari temukan hingga hadir — satu platform untuk semua kebutuhan eventmu.</p>
      </div>

      <div class="features-grid">

        <div class="feature-card reveal reveal-delay-1">
          <div class="feature-card__icon">📍</div>
          <div class="feature-card__title">Event di Peta</div>
          <p class="feature-card__desc">
            Temukan lokasi event secara visual melalui peta interaktif real-time. Filter berdasarkan jarak, kategori, atau tanggal.
          </p>
        </div>

        <div class="feature-card reveal reveal-delay-2">
          <div class="feature-card__icon">🎟</div>
          <div class="feature-card__title">Pesan Tiket Instan</div>
          <p class="feature-card__desc">
            Beli tiket online tanpa antrean. Pembayaran via transfer bank, dompet digital, atau kartu kredit.
          </p>
        </div>

        <div class="feature-card reveal reveal-delay-3">
          <div class="feature-card__icon">📱</div>
          <div class="feature-card__title">QR Check-In</div>
          <p class="feature-card__desc">
            Masuk event lebih cepat menggunakan QR Code di smartphone. Tidak perlu cetak tiket fisik.
          </p>
        </div>

      </div>
    </div>
  </section>

  <!-- ============================
       EVENT POPULER
  ============================= -->
  <section id="event-section" class="section">
    <div class="container">

      <div class="events-header">
        <div class="reveal">
          <div class="section-tag">🔥 Trending</div>
          <h2 class="section-title">Event Populer</h2>
        </div>
        <a href="/events" class="btn-secondary reveal">Lihat semua →</a>
      </div>

      <div class="events-grid">

        <div class="event-card reveal reveal-delay-1">
          <div class="event-card__media">
            <img src="https://images.pexels.com/photos/1190297/pexels-photo-1190297.jpeg" alt="Festival Musik" loading="lazy">
            <span class="event-card__badge">Musik</span>
          </div>
          <div class="event-card__body">
            <div class="event-card__meta">
              <span class="event-card__date">📅 12 Jul 2025</span>
            </div>
            <h3 class="event-card__title">Festival Musik Nusantara</h3>
            <p class="event-card__desc">Festival musik terbesar tahun ini dengan 30+ penampil dari seluruh Indonesia.</p>
            <div class="event-card__footer">
              <span class="event-card__price">Rp 150.000</span>
              <span class="event-card__btn">Beli tiket →</span>
            </div>
          </div>
        </div>

        <div class="event-card reveal reveal-delay-2">
          <div class="event-card__media">
            <img src="https://images.unsplash.com/photo-1556761175-b413da4baf72?w=600&q=75" alt="Seminar IT" loading="lazy">
            <span class="event-card__badge">Teknologi</span>
          </div>
          <div class="event-card__body">
            <div class="event-card__meta">
              <span class="event-card__date">📅 18 Jul 2025</span>
            </div>
            <h3 class="event-card__title">Seminar IT & AI 2025</h3>
            <p class="event-card__desc">Belajar teknologi terbaru bersama expert dari Google, Meta, dan startup unicorn Indonesia.</p>
            <div class="event-card__footer">
              <span class="event-card__price">Rp 75.000</span>
              <span class="event-card__btn">Beli tiket →</span>
            </div>
          </div>
        </div>

        <div class="event-card reveal reveal-delay-3">
          <div class="event-card__media">
            <img src="https://images.unsplash.com/photo-1515187029135-18ee286d815b?w=600&q=75" alt="Startup Networking" loading="lazy">
            <span class="event-card__badge">Bisnis</span>
          </div>
          <div class="event-card__body">
            <div class="event-card__meta">
              <span class="event-card__date">📅 25 Jul 2025</span>
            </div>
            <h3 class="event-card__title">Startup Networking Night</h3>
            <p class="event-card__desc">Networking dan kolaborasi bisnis dengan 200+ founder, investor, dan pelaku industri kreatif.</p>
            <div class="event-card__footer">
              <span class="event-card__price">Gratis</span>
              <span class="event-card__btn">Daftar →</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ============================
       CTA — JADI EO
  ============================= -->
  <section id="eo-section" class="eo-section section--alt">
    <div class="eo-inner">

      <div class="reveal">
        <div class="section-tag">🚀 Untuk Event Organizer</div>
        <h2 class="section-title">
          Ingin Mengadakan<br>Event Impianmu?
        </h2>
        <p>
          Daftarkan organisasimu sebagai Event Organizer dan promosikan event kepada ribuan pengguna aktif di seluruh Indonesia.
        </p>
      </div>

      <div class="eo-perks reveal reveal-delay-1">
        <div class="eo-perk"><span class="eo-perk-dot"></span> Dashboard analitik lengkap</div>
        <div class="eo-perk"><span class="eo-perk-dot"></span> Manajemen tiket otomatis</div>
        <div class="eo-perk"><span class="eo-perk-dot"></span> Promosi ke ribuan user</div>
        <div class="eo-perk"><span class="eo-perk-dot"></span> Pembayaran aman & cepat</div>
      </div>

      <div class="reveal reveal-delay-2">
        <a href="/eo-register" class="btn-primary" style="font-size:1rem; padding: 0.9rem 2.5rem;">
          Daftar Sebagai EO — Gratis
        </a>
      </div>

    </div>
  </section>

  <!-- ============================
       FOOTER
  ============================= -->
  <footer>
    <div class="footer-inner">
      <div class="footer-brand">Even<span>Tour</span></div>

      <ul class="footer-links">
        <li><a href="#">Tentang</a></li>
        <li><a href="#">Blog</a></li>
        <li><a href="#">Karir</a></li>
        <li><a href="#">Kontak</a></li>
      </ul>

      <div class="footer-copy">© 2025 EvenTour. Hak cipta dilindungi.</div>
    </div>
  </footer>


  <!-- ============================
       SCRIPTS
  ============================= -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    /* --- NAVBAR SCROLL --- */
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    });

    /* --- MOBILE TOGGLE --- */
    const toggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    toggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
    });

    /* --- SCROLL REVEAL --- */
    const revealEls = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(el => revealObserver.observe(el));

    /* --- ANIMATED COUNTERS --- */
    function animateCount(el, target, suffix) {
      const duration = 1800;
      const start = performance.now();
      const update = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(eased * target);
        if (progress < 1) requestAnimationFrame(update);
      };
      requestAnimationFrame(update);
    }

    const counterObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const target = parseInt(el.dataset.target);
          animateCount(el, target);
          counterObserver.unobserve(el);
        }
      });
    }, { threshold: 0.5 });

    document.querySelectorAll('.count-num').forEach(el => counterObserver.observe(el));

    /* --- LEAFLET MAP (DARK TILES) --- */
    const map = L.map('map', {
      center: [-7.0051, 110.4381],
      zoom: 7,
      zoomControl: true,
    });

    L.tileLayer(
      'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
      {
        attribution: '© <a href="https://carto.com/">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19,
      }
    ).addTo(map);

    /* Custom SVG marker */
    function makeIcon(color) {
      return L.divIcon({
        className: '',
        html: `<svg width="32" height="40" viewBox="0 0 32 40" xmlns="http://www.w3.org/2000/svg">
          <path d="M16 0C7.163 0 0 7.163 0 16c0 10 16 24 16 24s16-14 16-24C32 7.163 24.837 0 16 0z" fill="${color}" opacity="0.9"/>
          <circle cx="16" cy="16" r="7" fill="#09090b"/>
        </svg>`,
        iconSize: [32, 40],
        iconAnchor: [16, 40],
        popupAnchor: [0, -38],
      });
    }

    const events = [
      { lat: -7.8014, lng: 110.3647, title: '🎵 Festival Musik Nusantara', date: '12 Jul 2025', price: 'Rp 150.000', color: '#e8ff6a' },
      { lat: -6.9175, lng: 107.6191, title: '💻 Seminar IT & AI 2025',   date: '18 Jul 2025', price: 'Rp 75.000',  color: '#6affda' },
      { lat: -7.2575, lng: 112.7521, title: '🚀 Startup Networking Night',date: '25 Jul 2025', price: 'Gratis',      color: '#ff6a9e' },
      { lat: -6.2088, lng: 106.8456, title: '🎨 Jakarta Art & Design Week',date: '2 Agt 2025', price: 'Rp 50.000', color: '#e8ff6a' },
      { lat: -8.6705, lng: 115.2126, title: '🌊 Bali Beach Festival',     date: '8 Agt 2025', price: 'Rp 200.000', color: '#6affda' },
    ];

    events.forEach(ev => {
      L.marker([ev.lat, ev.lng], { icon: makeIcon(ev.color) })
        .addTo(map)
        .bindPopup(`
          <div style="font-family:'DM Sans',sans-serif; min-width:180px; padding:4px 0">
            <strong style="font-size:0.9rem;display:block;margin-bottom:4px">${ev.title}</strong>
            <span style="font-size:0.75rem;opacity:0.6">📅 ${ev.date}</span><br>
            <span style="font-size:0.85rem;font-weight:600;color:#e8ff6a">${ev.price}</span>
          </div>
        `, { maxWidth: 220 });
    });
  </script>

</body>
</html>