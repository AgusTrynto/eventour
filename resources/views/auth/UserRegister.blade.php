<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - EvenTour</title>

    @vite(['resources/css/auth/register.css', 'resources/js/app.js'])
</head>

<body class="register-page">

    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">
            Even<span>Tour</span>
        </a>

        <a href="/login" class="login-link">
            Sudah punya akun?
        </a>
    </header>

    <main class="main-content">
        <div class="register-card">

            <div class="heading">
                <span class="badge">MULAI PETUALANGANMU</span>

                <h1>
                    Buat Akun Baru
                </h1>

                <p>
                    Temukan event menarik, beli tiket, dan bangun komunitasmu bersama EvenTour.
                </p>
            </div>

            <form action="/register" method="POST" class="register-form">
                @csrf

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input
                        type="text"
                        name="name"
                        placeholder="Masukkan nama lengkap"
                        required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        placeholder="nama@email.com"
                        required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        required>
                </div>

                <div class="checkbox">
                    <input type="checkbox" required>

                    <span>
                        Saya menyetujui
                        <a href="#">Syarat & Ketentuan</a>
                        dan
                        <a href="#">Kebijakan Privasi</a>
                    </span>
                </div>

                <button type="submit" class="btn-register">
                    Daftar Sekarang
                </button>
            </form>

            <div class="divider"></div>

            <p class="eo-link">
                Ingin mendaftarkan Event Organizer?
                <a href="/eo-register">Daftar sebagai EO</a>
            </p>

        </div>
    </main>

    <footer>
        Copyright 2026 EvenTour. All Rights Reserved.
    </footer>

</body>
</html>
