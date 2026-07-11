<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - EvenTour</title>
    @vite(['resources/css/user/dashboard.css', 'resources/css/user/navbar.css', 'resources/js/app.js'])
</head>

<body class="dashboard-page">
    <div class="bg-glow"></div>

    @include('user.partials.navbar', ['active' => 'profile', 'user' => $user])

    <main class="main-content">
        <div class="container-custom">
            @if (session('success'))
                <div class="alert alert-success">
                    <x-icon name="check-circle" :size="18" />
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">
                    <x-icon name="alert-triangle" :size="18" />
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">
                    <x-icon name="alert-triangle" :size="18" />
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="profile-page-grid">
                <section class="card profile-summary-card">
                    <div class="profile-info">
                        <div class="profile-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                        <div class="profile-details">
                            <span class="profile-name">{{ $user->name }}</span>
                            <span class="profile-email">{{ $user->email }}</span>
                            <span class="profile-joined">Bergabung {{ $user->created_at?->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>

                    <div class="refund-readiness {{ $user->hasRefundDestination() ? 'ready' : 'missing' }}">
                        <x-icon name="{{ $user->hasRefundDestination() ? 'check-circle' : 'alert-triangle' }}" :size="18" />
                        <span>
                            {{ $user->hasRefundDestination() ? 'Data refund sudah lengkap' : 'Data refund belum lengkap' }}
                        </span>
                    </div>
                </section>

                <section class="card profile-form-card">
                    <div class="card-header">
                        <div>
                            <span class="badge">TUJUAN REFUND</span>
                            <h2>Rekening atau e-wallet</h2>
                        </div>
                    </div>

                    <form action="{{ route('profile.refund-destination.update') }}" method="POST" class="profile-form">
                        @csrf
                        @method('PUT')

                        <label class="profile-field">
                            <span>Bank/e-wallet</span>
                            <select name="refund_destination_channel_code" required>
                                <option value="">Pilih tujuan refund</option>
                                @foreach ($refundChannels as $group)
                                    <optgroup label="{{ $group['label'] }}">
                                        @foreach ($group['channels'] as $code => $label)
                                            <option value="{{ $code }}" @selected(old('refund_destination_channel_code', $user->refund_destination_channel_code) === $code)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </label>

                        <label class="profile-field">
                            <span>Nomor rekening/e-wallet</span>
                            <input
                                type="text"
                                name="refund_destination_account_number"
                                maxlength="50"
                                value="{{ old('refund_destination_account_number', $user->refund_destination_account_number) }}"
                                placeholder="Contoh: 081234567890 atau 1234567890"
                                required
                            >
                        </label>

                        <label class="profile-field">
                            <span>Nama pemilik</span>
                            <input
                                type="text"
                                name="refund_destination_account_name"
                                maxlength="255"
                                value="{{ old('refund_destination_account_name', $user->refund_destination_account_name) }}"
                                placeholder="Nama sesuai rekening/e-wallet"
                                required
                            >
                        </label>

                        <div class="escrow-note profile-note">
                            <x-icon name="shield" :size="16" />
                            Data ini dipakai jika refund otomatis ke metode pembayaran asal tidak didukung oleh channel pembayaran.
                        </div>

                        <button type="submit" class="btn-profile-save">
                            Simpan Data Refund
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <footer>Copyright 2026 EvenTour. All Rights Reserved.</footer>
</body>
</html>
