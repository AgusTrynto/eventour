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
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimal 8 karakter"
                            required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Ulangi password"
                            required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', this)" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <span id="password-match-status" class="password-hint">Ketik ulang password untuk konfirmasi</span>
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
