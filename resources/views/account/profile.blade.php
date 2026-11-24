@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    <div class="page-head"><h1>Profil</h1></div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="panel panel-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="avatar" style="width:64px;height:64px;font-size:1.5rem" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</div>
                    <div>
                        <h2 class="h4 mb-0">{{ $user->name }}</h2>
                        <span class="text-muted">{{ $user->email }}</span>
                        <div class="mt-1 d-flex flex-wrap gap-1">
                            @if ($user->email_verified_at)
                                <span class="badge-soft" style="background:#e3f1e5;color:#1e5b2b"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Email terverifikasi</span>
                            @endif
                            @if ($user->google_id)
                                <span class="badge-soft" style="background:#eceff3;color:#3b4452"><i class="fa-brands fa-google" aria-hidden="true"></i> Terhubung dengan Google</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6 col-sm-4"><div class="stat"><div class="num">{{ $ordersCount }}</div><div class="label">Pesanan</div></div></div>
                    <div class="col-6 col-sm-4"><div class="stat"><div class="num fs-5">{{ $user->role_label }}</div><div class="label">Peran</div></div></div>
                    <div class="col-6 col-sm-4">
                        <div class="stat">
                            <div class="num text-capitalize">{{ $user->member }}</div>
                            <div class="label">Member{{ $user->discountRate() ? ' · diskon '.($user->discountRate() * 100).'%' : '' }}</div>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0">Bergabung sejak {{ $user->created_at->translatedFormat('d F Y') }}</p>
            </div>

            <div class="panel panel-body mt-4">
                <h2 class="h5 mb-2">Nama pengguna</h2>
                @php($nameAvailableAt = $user->nameChangeAvailableAt())
                @if ($nameAvailableAt)
                    <p class="text-muted small mb-3">
                        Nama pengguna baru saja diganti. Kamu bisa menggantinya lagi pada
                        <strong>{{ $nameAvailableAt->translatedFormat('d F Y, H:i') }} WIB</strong>.
                    </p>
                    <div class="mb-3">
                        <input type="text" class="form-control" value="{{ $user->name }}" aria-label="Nama pengguna" disabled>
                    </div>
                    <button class="btn btn-primary" disabled>Simpan nama</button>
                @else
                    <p class="text-muted small mb-3">
                        Nama ini tampil di akun dan ulasanmu. Setelah diganti, nama baru bisa diganti lagi setelah
                        {{ \App\Models\User::NAME_CHANGE_COOLDOWN_DAYS }} hari.
                    </p>
                    <form action="{{ route('profile.name') }}" method="POST">
                        @csrf @method('PATCH')
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama pengguna</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" maxlength="100" autocomplete="nickname" required>
                        </div>
                        <button class="btn btn-primary">Simpan nama</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            @if ($user->password)
                <div class="panel panel-body">
                    <h2 class="h5 mb-3">Ubah password</h2>
                    <form action="{{ route('password.update') }}" method="POST">
                        @csrf @method('PATCH')
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password saat ini</label>
                            <input type="password" id="current_password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password baru</label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8" required>
                            <div class="form-text">Minimal 8 karakter, dengan huruf besar, huruf kecil, angka, dan simbol.</div>
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Ulangi password baru</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                        </div>
                        <button class="btn btn-primary">Simpan password</button>
                    </form>
                </div>
            @else
                <div class="panel panel-body">
                    <h2 class="h5 mb-2">Buat password</h2>
                    <p class="text-muted mb-3">
                        Akun kamu terdaftar menggunakan Google, sehingga belum memiliki password. Jika ingin login menggunakan
                        email dan password, silakan buat password baru di bawah ini. Kamu tetap bisa menggunakan akun Google
                        untuk login kapan saja.
                    </p>
                    <form action="{{ route('password.create') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="password" class="form-label">Password baru</label>
                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8" required>
                            <div class="form-text">Minimal 8 karakter, dengan huruf besar, huruf kecil, angka, dan simbol.</div>
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Ulangi password baru</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                        </div>
                        <button class="btn btn-primary">Buat password</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

@endsection
