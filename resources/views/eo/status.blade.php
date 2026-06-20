<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Akun EO - EvenTour</title>

    @vite(['resources/css/eo/status.css', 'resources/js/app.js'])
</head>

<body class="status-page">

    <div class="bg-glow"></div>

    <header class="container-custom">

        <div style="display:flex;align-items:center;gap:16px;">
            <a href="{{ route('dashboard') }}" class="btn-back">
                ← Kembali
            </a>

            <a href="/" class="logo">Even<span>Tour</span></a>
        </div>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-logout">Logout</button>
        </form>

    </header>

    <main class="main-content">
        <div class="status-card">

            @if ($organizer->status === 'pending')
                <div class="status-icon pending">⏳</div>
                <span class="badge badge-pending">MENUNGGU PERSETUJUAN</span>
                <h1>Akun EO Sedang Diverifikasi</h1>
                <p>
                    Terima kasih telah mendaftar sebagai Event Organizer di EvenTour.
                    Tim kami sedang meninjau data <strong>{{ $organizer->org_name }}</strong>.
                </p>
                <p class="sub-text">
                    Proses verifikasi biasanya memakan waktu 1–3 hari kerja.
                    Kamu akan menerima notifikasi melalui email setelah akun disetujui.
                </p>

                <div class="info-box">
                    <div class="info-row">
                        <span>Nama Organisasi</span>
                        <strong>{{ $organizer->org_name }}</strong>
                    </div>
                    <div class="info-row">
                        <span>Email</span>
                        <strong>{{ $organizer->user->email }}</strong>
                    </div>
                    <div class="info-row">
                        <span>Status</span>
                        <strong class="status-text-pending">Pending</strong>
                    </div>
                </div>

            @elseif ($organizer->status === 'rejected')
                <div class="status-icon rejected">❌</div>
                <span class="badge badge-rejected">PENDAFTARAN DITOLAK</span>
                <h1>Akun EO Tidak Disetujui</h1>
                <p>
                    Maaf, pendaftaran EO untuk <strong>{{ $organizer->org_name }}</strong>
                    tidak dapat disetujui saat ini.
                </p>

                @if ($organizer->reject_reason ?? false)
                    <div class="reason-box">
                        <strong>Alasan:</strong> {{ $organizer->reject_reason }}
                    </div>
                @endif

                <p class="sub-text">
                    Jika ini adalah kesalahan atau kamu ingin mengajukan ulang,
                    silakan hubungi tim support kami.
                </p>

                <a href="mailto:support@eventour.id" class="btn-contact">
                    Hubungi Support
                </a>
            @endif

        </div>
    </main>

    <footer>© 2026 EvenTour. All Rights Reserved.</footer>

</body>

</html>