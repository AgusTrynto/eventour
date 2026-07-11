<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - EvenTour</title>

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
                <span class="badge">PASSWORD BARU</span>
                <h1>Reset Password</h1>
                <p>Buat password baru minimal 8 karakter untuk akun EvenTour kamu.</p>
            </div>

            @if (session('error'))
                <div class="error-box">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" class="login-form">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        placeholder="nama@email.com"
                        value="{{ old('email', $email) }}"
                        required>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="reset-password"
                            name="password"
                            placeholder="Minimal 8 karakter"
                            required>
                        <button type="button" class="password-toggle" onclick="togglePassword('reset-password', this)" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="reset-password-confirmation"
                            name="password_confirmation"
                            placeholder="Ulangi password baru"
                            required>
                        <button type="button" class="password-toggle" onclick="togglePassword('reset-password-confirmation', this)" aria-label="Toggle password visibility">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Simpan Password Baru
                </button>
            </form>

        </div>
    </main>

    <footer>
        Copyright 2026 EvenTour. All Rights Reserved.
    </footer>

</body>
</html>
