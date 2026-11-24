<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SignupVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau password salah.');
        }

        $request->session()->regenerate();

        return redirect()->intended(
            Auth::user()->canManageShop() ? route('dashboard') : route('home')
        );
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    /** Kirim kode verifikasi ke email yang akan didaftarkan (dipanggil tombol "Kirim kode" di form daftar). */
    public function sendSignupCode(Request $request)
    {
        $this->normalizeEmail($request);

        $data = $request->validate(['email' => $this->emailRules()], $this->emailMessages());

        $wait = SignupVerification::resendIn($data['email']);

        if ($wait > 0) {
            return response()->json([
                'message' => "Tunggu {$wait} detik sebelum meminta kode baru.",
                'resend_in' => $wait,
            ], 429);
        }

        if (! SignupVerification::send($data['email'])) {
            return response()->json(['message' => 'Email gagal dikirim. Coba lagi beberapa saat lagi.'], 503);
        }

        return response()->json([
            'message' => "Kode dikirim ke {$data['email']}. Belum masuk? Cek folder Spam.",
            'resend_in' => SignupVerification::RESEND_SECONDS,
        ]);
    }

    public function register(Request $request)
    {
        $this->normalizeEmail($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => $this->emailRules(),
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'code' => ['required', 'digits:6'],
        ], $this->emailMessages() + [
            'code.required' => 'Klik "Kirim kode", lalu masukkan kode 6 digit dari emailmu.',
            'code.digits' => 'Kode verifikasi terdiri dari 6 angka.',
        ]);

        // Akun hanya dibuat kalau pemilik email terbukti: kode salah = pendaftaran gagal.
        if ($error = SignupVerification::check($data['email'], $data['code'])) {
            throw ValidationException::withMessages(['code' => $error]);
        }

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // email_verified_at sengaja tidak fillable; diisi di sini karena emailnya sudah terbukti lewat kode.
        $user->forceFill(['email_verified_at' => now()])->save();

        SignupVerification::forget($data['email']);

        return redirect()->route('login')->with('success', 'Akun berhasil dibuat, silakan masuk.');
    }

    /** Rapikan dulu: huruf kecil & tanpa spasi, agar "Nama@Gmail.com " tidak dianggap email lain. */
    private function normalizeEmail(Request $request): void
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
    }

    private function emailRules(): array
    {
        // `dns`: domainnya harus benar-benar ada dan menerima email (a@a.a ditolak).
        // Pengecekan DNS bisa dimatikan (CHECK_EMAIL_DNS=false) untuk tes tanpa internet.
        $format = config('app.check_email_dns', true) ? 'email:rfc,dns' : 'email:rfc';

        // `regex`: akhiran domain minimal 2 huruf.
        return ['required', 'string', 'max:255', $format, 'regex:/^[^@\s]+@[^@\s]+\.[A-Za-z]{2,}$/', 'unique:users,email'];
    }

    private function emailMessages(): array
    {
        $invalidEmail = 'Gunakan alamat email aktif yang valid, misalnya nama@gmail.com.';

        return [
            'email.email' => $invalidEmail,
            'email.regex' => $invalidEmail,
            'email.unique' => 'Email ini sudah terdaftar.',
        ];
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kamu sudah keluar.');
    }
}
