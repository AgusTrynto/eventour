<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - EvenTour</title>

    @vite(['resources/css/auth/login.css', 'resources/js/app.js'])
</head>

<body class="login-page">

    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">
            Even<span>Tour</span>
        </a>

        <a href="{{ route('login') }}" class="register-link">
            Kembali masuk
        </a>
    </header>

    <main class="main-content">
        <div class="login-card">

            <div class="heading">
                <span class="badge">RESET PASSWORD</span>
                <h1>Lupa Password?</h1>
                <p>Masukkan email akunmu. Kami akan mengirim link untuk membuat password baru.</p>
            </div>

            @if (session('success'))
                <div class="alert-success">
                    <x-icon name="check-circle" :size="18" />
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="error-box">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="login-form">
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

                <button type="submit" class="btn-login">
                    Kirim Link Reset
                </button>
            </form>

            <div class="divider"></div>

            <p class="eo-link">
                Sudah ingat password?
                <a href="{{ route('login') }}">Masuk sekarang</a>
            </p>

        </div>
    </main>

    <footer>
        Copyright 2026 EvenTour. All Rights Reserved.
    </footer>

</body>
</html>
