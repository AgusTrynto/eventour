<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - EvenTour</title>

    @vite(['resources/css/auth/login.css', 'resources/js/app.js'])
</head>

<body class="login-page">

    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">
            Even<span>Tour</span>
        </a>

        <a href="/register" class="register-link">
            Belum punya akun?
        </a>
    </header>

    <main class="main-content">
        <div class="login-card">

            <div class="heading">
                <span class="badge">SELAMAT DATANG KEMBALI</span>
                <h1>Masuk ke Akun</h1>
                <p>Lanjutkan perjalananmu. Temukan event seru dan kelola tiketmu di EvenTour.</p>
            </div>

            {{-- Flash sukses dari register --}}
            @if (session('success'))
                <div class="alert-success">
                    <x-icon name="check-circle" :size="18" />
                    {{ session('success') }}
                </div>
            @endif

            {{-- Error login --}}
            @if (session('error'))
                <div class="error-box">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST" class="login-form">
                @csrf

                <div class="form-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        placeholder="nama@email.com"
                        value="{{ old('email') }}"
                        required>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Masukkan password"
                        required>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Ingat saya</span>
                    </label>

                    <a href="/forgot-password" class="forgot-link">Lupa password?</a>
                </div>

                <button type="submit" class="btn-login">
                    Masuk Sekarang
                </button>
            </form>

            <div class="divider"></div>

            <p class="eo-link">
                Login sebagai Event Organizer?
                <a href="/eo-login">Masuk sebagai EO</a>
            </p>

        </div>
    </main>

    <footer>
        Copyright 2026 EvenTour. All Rights Reserved.
    </footer>

</body>
</html>
