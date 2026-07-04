<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi EvenTour</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f1117;
            color: #ffffff;
            padding: 40px 20px;
        }

        .wrapper {
            max-width: 520px;
            margin: 0 auto;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 32px;
        }

        .logo span {
            color: #d8ff4f;
        }

        .card {
            background: rgba(25, 25, 30, 0.9);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 24px;
            padding: 40px;
        }

        .label {
            font-size: 11px;
            letter-spacing: 2px;
            font-weight: 700;
            color: #d8ff4f;
            margin-bottom: 12px;
        }

        h1 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .greeting {
            color: #9ca3af;
            margin-bottom: 28px;
            line-height: 1.6;
        }

        .otp-box {
            background: rgba(216, 255, 79, 0.08);
            border: 1px solid rgba(216, 255, 79, 0.25);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin-bottom: 28px;
        }

        .otp-label {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .otp-code {
            font-size: 48px;
            font-weight: 800;
            color: #d8ff4f;
            letter-spacing: 10px;
        }

        .otp-expiry {
            margin-top: 10px;
            font-size: 12px;
            color: #6b7280;
        }

        .warning {
            background: rgba(255, 80, 80, 0.08);
            border: 1px solid rgba(255, 80, 80, 0.2);
            border-radius: 12px;
            padding: 14px 18px;
            font-size: 13px;
            color: #ff6b6b;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .divider {
            border-top: 1px solid rgba(255,255,255,.08);
            margin: 28px 0;
        }

        .footer-text {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.6;
        }

        .footer-copy {
            text-align: center;
            margin-top: 28px;
            font-size: 12px;
            color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="wrapper">

        <div class="logo">Even<span>Tour</span></div>

        <div class="card">
            <div class="label">VERIFIKASI AKUN</div>
            <h1>Kode OTP Kamu</h1>

            <p class="greeting">
                Halo <strong>{{ $name }}</strong>, <br>
                Masukkan kode berikut untuk menyelesaikan pendaftaran akunmu di EvenTour.
            </p>

            <div class="otp-box">
                <div class="otp-label">KODE VERIFIKASI</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-expiry">Berlaku selama <strong>10 menit</strong></div>
            </div>

            <div class="warning">
                Jangan bagikan kode ini kepada siapapun, termasuk tim EvenTour.
                Kami tidak pernah meminta kode OTP kamu.
            </div>

            <div class="divider"></div>

            <p class="footer-text">
                Jika kamu tidak merasa mendaftar di EvenTour, abaikan email ini.
                Tidak ada akun yang akan dibuat tanpa verifikasi.
            </p>
        </div>

        <p class="footer-copy">Copyright 2026 EvenTour. All Rights Reserved.</p>

    </div>
</body>
</html>
