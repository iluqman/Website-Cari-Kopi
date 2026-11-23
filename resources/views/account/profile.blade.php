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
        </div>
        <div class="col-lg-6">
            @if ($user->password)
                {{-- form "Ubah password" yang sudah ada, tidak diubah --}}
                <div class="panel panel-body">
                    <h2 class="h5 mb-3">Ubah password</h2>
                    ...
                </div>
            @else
                <div class="panel panel-body">
                    <h2 class="h5 mb-2">Keamanan akun</h2>
                    <p class="text-muted mb-3">
                        Akun ini masuk dengan Google, jadi tidak ada password di CariKopi.
                        Keamanan akunmu mengikuti pengaturan akun Google.
                    </p>
                    <a href="https://myaccount.google.com/security" target="_blank" rel="noopener"
                    class="btn btn-outline-secondary">Kelola keamanan Google</a>
                </div>
            @endif
        </div>
    </div>

@endsection
