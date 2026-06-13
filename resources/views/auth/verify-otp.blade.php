<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - EvenTour</title>

    @vite(['resources/css/auth/verify-otp.css', 'resources/js/app.js'])
</head>

<body class="otp-page">

    <div class="bg-glow"></div>

    <header class="container-custom">
        <a href="/" class="logo">
            Even<span>Tour</span>
        </a>
    </header>

    <main class="main-content">
        <div class="otp-card">

            <div class="heading">
                <div class="otp-icon">✉️</div>
                <span class="badge">VERIFIKASI EMAIL</span>

                <h1>Cek Emailmu</h1>

                <p>
                    Kami mengirim kode 6 digit ke<br>
                    <strong class="email-highlight">{{ $email }}</strong>
                </p>
            </div>

            {{-- Flash success (resend) --}}
            @if (session('success'))
                <div class="alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('register.otp.verify') }}" method="POST" class="otp-form">
                @csrf

                <div class="otp-inputs">
                    <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]" autofocus>
                    <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]">
                    <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]">
                    <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]">
                    <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]">
                    <input type="text" maxlength="1" class="otp-digit" inputmode="numeric" pattern="[0-9]">
                </div>

                {{-- Hidden field yang dikirim ke controller --}}
                <input type="hidden" name="otp" id="otp-hidden">

                @error('otp')
                    <div class="error-box">{{ $message }}</div>
                @enderror

                @error('resend')
                    <div class="error-box">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn-verify" id="btn-verify" disabled>
                    Verifikasi
                </button>
            </form>

            <div class="resend-section">
                <span id="countdown-text" class="countdown-text">
                    Kirim ulang dalam <span id="countdown">60</span> detik
                </span>

                <form action="{{ route('register.otp.resend') }}" method="POST" id="resend-form">
                    @csrf
                    <button type="submit" class="btn-resend" id="btn-resend" disabled>
                        Kirim Ulang OTP
                    </button>
                </form>
            </div>

            <a href="{{ route('register') }}" class="back-link">
                ← Kembali & ubah email
            </a>

        </div>
    </main>

    <footer>
        © 2026 EvenTour. All Rights Reserved.
    </footer>

    <script>
        // ── OTP digit input logic ──────────────────────────────
        const digits   = document.querySelectorAll('.otp-digit');
        const hidden   = document.getElementById('otp-hidden');
        const btnVerify = document.getElementById('btn-verify');

        digits.forEach((input, i) => {
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/\D/g, '');
                e.target.value = val;

                if (val && i < digits.length - 1) {
                    digits[i + 1].focus();
                }

                syncHidden();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && i > 0) {
                    digits[i - 1].focus();
                }
            });

            // Support paste on any digit
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                pasted.split('').forEach((char, j) => {
                    if (digits[j]) digits[j].value = char;
                });
                if (digits[pasted.length - 1]) digits[pasted.length - 1].focus();
                syncHidden();
            });
        });

        function syncHidden() {
            const val = [...digits].map(d => d.value).join('');
            hidden.value = val;
            btnVerify.disabled = val.length !== 6;
        }

        // ── Countdown timer ────────────────────────────────────
        let seconds = 60;
        const countdownEl  = document.getElementById('countdown');
        const countdownText = document.getElementById('countdown-text');
        const btnResend     = document.getElementById('btn-resend');

        const timer = setInterval(() => {
            seconds--;
            countdownEl.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(timer);
                countdownText.style.display = 'none';
                btnResend.disabled = false;
            }
        }, 1000);
    </script>

</body>
</html>