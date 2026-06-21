<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar EO - EvenTour</title>

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
                <span class="badge">JADI MITRA EVENTOUR</span>

                <h1>
                    Daftar Sebagai EO
                </h1>

                <p>
                    Buat dan kelola eventmu sendiri, jangkau ribuan pengunjung melalui EvenTour.
                </p>
            </div>

            {{-- Flash error --}}
            @if (session('error'))
                <div class="error-box">{{ session('error') }}</div>
            @endif

            <form action="{{ route('eo.register.submit') }}" method="POST" class="register-form">
                @csrf

                <div class="form-group">
                    <label>Nama Organisasi / EO</label>
                    <input type="text" name="org_name" placeholder="Contoh: Java Production"
                        value="{{ old('org_name') }}" required>
                    @error('org_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Nama Penanggung Jawab</label>
                    <input type="text" name="name" placeholder="Masukkan nama lengkap" value="{{ old('name') }}"
                        required>
                    @error('name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="nama@email.com" value="{{ old('email') }}" required>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="text" name="phone" placeholder="08xxxxxxxxxx" value="{{ old('phone') }}" required>
                    @error('phone')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Alamat (opsional)</label>
                    <input type="text" name="address" placeholder="Alamat organisasi/EO" value="{{ old('address') }}">
                    @error('address')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>


                <div class="form-group">
                    <label>Nama Bank</label>
                    <select name="bank_name" required>
                        <option value="">Pilih bank</option>
                        <option value="BCA" {{ old('bank_name') === 'BCA' ? 'selected' : '' }}>BCA</option>
                        <option value="BNI" {{ old('bank_name') === 'BNI' ? 'selected' : '' }}>BNI</option>
                        <option value="BRI" {{ old('bank_name') === 'BRI' ? 'selected' : '' }}>BRI</option>
                        <option value="Mandiri" {{ old('bank_name') === 'Mandiri' ? 'selected' : '' }}>Mandiri</option>
                        <option value="CIMB Niaga" {{ old('bank_name') === 'CIMB Niaga' ? 'selected' : '' }}>CIMB Niaga
                        </option>
                        <option value="Lainnya" {{ old('bank_name') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('bank_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Nomor Rekening</label>
                    <input type="text" name="bank_account_number" placeholder="Contoh: 1234567890"
                        value="{{ old('bank_account_number') }}" required>
                    @error('bank_account_number')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Nama Pemilik Rekening</label>
                    <input type="text" name="bank_account_name" placeholder="Sesuai buku tabungan"
                        value="{{ old('bank_account_name') }}" required>
                    @error('bank_account_name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Minimal 8 karakter" required>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="checkbox">
                    <input type="checkbox" name="agree" required>

                    <span>
                        Saya menyetujui
                        <a href="#">Syarat & Ketentuan</a>
                        dan
                        <a href="#">Kebijakan Privasi</a>
                        sebagai Event Organizer
                    </span>
                </div>

                <button type="submit" class="btn-register">
                    Daftar Sebagai EO
                </button>
            </form>

            <div class="divider"></div>

            <p class="eo-link">
                Ingin mendaftar sebagai peserta biasa?
                <a href="/register">Daftar Akun Biasa</a>
            </p>

        </div>
    </main>

    <footer>
        © 2026 EvenTour. All Rights Reserved.
    </footer>

</body>

</html>