@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <h1 class="h2 mb-1">Buat akun</h1>
    <p class="text-muted mb-4">Gratis, dan hanya butuh satu menit.</p>

    <form action="{{ route('register.store') }}" method="POST" novalidate>
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Nama</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror"
                   autocomplete="name" required autofocus>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <div class="input-group">
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror"
                       autocomplete="email" placeholder="nama@gmail.com" required>
                <button type="button" id="send-code" class="btn btn-outline-secondary" data-url="{{ route('register.code') }}">Kirim kode</button>
            </div>
            <div id="code-status" class="form-text" aria-live="polite">Gunakan email aktif, misalnya Gmail atau Yahoo.</div>
        </div>
        <div class="mb-3">
            <label for="code" class="form-label">Kode verifikasi</label>
            <input type="text" id="code" name="code" value="{{ old('code') }}" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                   autocomplete="one-time-code" placeholder="6 digit dari email" required
                   class="form-control @error('code') is-invalid @enderror">
            <div class="form-text">Klik <strong>Kirim kode</strong>, lalu isi kode yang masuk ke emailmu (cek folder Spam).</div>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                   autocomplete="new-password" minlength="8" required>
            <div class="form-text">Minimal 8 karakter, dengan huruf besar, huruf kecil, angka, dan simbol.</div>
        </div>
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">Ulangi password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                   autocomplete="new-password" required>
        </div>
        <button class="btn btn-primary w-100 py-2">Daftar</button>
        <div class="d-flex align-items-center my-3">
            <hr class="flex-grow-1 m-0"><span class="px-3 text-muted small">atau</span><hr class="flex-grow-1 m-0">
        </div>
        <a href="{{ route('google.redirect') }}" class="btn btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
            @include('partials.google-logo')
            <span>Daftar dengan Google</span>
        </a>
    </form>

    <p class="text-center text-muted mt-4 mb-0">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>

    <script>
        (() => {
            const btn = document.getElementById('send-code');
            const email = document.getElementById('email');
            const code = document.getElementById('code');
            const status = document.getElementById('code-status');
            const token = document.querySelector('input[name="_token"]').value;
            const label = btn.textContent;
            let timer = null;

            const say = (text, cls) => { status.textContent = text; status.className = 'form-text ' + (cls || ''); };

            const countdown = (seconds) => {
                clearInterval(timer);
                let wait = seconds;
                btn.disabled = true;
                const tick = () => {
                    if (wait <= 0) {
                        clearInterval(timer);
                        btn.disabled = false;
                        btn.textContent = label;
                        return;
                    }
                    btn.textContent = 'Kirim ulang (' + wait + ')';
                    wait--;
                };
                timer = setInterval(tick, 1000);
                tick();
            };

            btn.addEventListener('click', async () => {
                if (!email.value.trim()) { email.focus(); say('Isi email dulu.', 'text-danger'); return; }

                btn.disabled = true;
                btn.textContent = 'Mengirim...';
                say('');

                try {
                    const res = await fetch(btn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ email: email.value }),
                    });
                    const data = await res.json().catch(() => ({}));

                    if (res.ok) {
                        say(data.message, 'text-success');
                        countdown(data.resend_in || 60);
                        code.focus();
                        return;
                    }

                    const msg = (data.errors && data.errors.email && data.errors.email[0]) || data.message || 'Kode gagal dikirim. Coba lagi.';
                    say(msg, 'text-danger');
                    if (res.status === 429 && data.resend_in) { countdown(data.resend_in); return; }
                } catch (e) {
                    say('Tidak bisa menghubungi server. Periksa koneksimu.', 'text-danger');
                }

                btn.disabled = false;
                btn.textContent = label;
            });
        })();
    </script>
@endsection
