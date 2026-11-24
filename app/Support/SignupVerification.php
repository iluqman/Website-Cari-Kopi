<?php

namespace App\Support;

use App\Mail\VerificationCodeMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Kode verifikasi email SEBELUM akun dibuat.
 *
 * Kode disimpan di cache (dalam bentuk hash) dan terikat ke satu alamat email,
 * jadi tidak ada baris database untuk email yang belum terbukti miliknya.
 */
class SignupVerification
{
    public const TTL_MINUTES = 10;
    public const RESEND_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;

    private static function key(string $email): string
    {
        return 'signup-code:'.sha1(Str::lower(trim($email)));
    }

    /** Sisa detik sebelum kode baru boleh diminta untuk email ini (0 = boleh sekarang). */
    public static function resendIn(string $email): int
    {
        $data = Cache::get(self::key($email));

        if (! $data) {
            return 0;
        }

        return max(0, self::RESEND_SECONDS - (now()->timestamp - $data['sent_at']));
    }

    /** Buat kode baru (menggantikan yang lama) lalu kirim lewat email. false = email gagal terkirim. */
    public static function send(string $email): bool
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(self::key($email), [
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->timestamp,
            'sent_at' => now()->timestamp,
            'attempts' => 0,
        ], now()->addMinutes(self::TTL_MINUTES));

        try {
            Mail::to($email)->send(new VerificationCodeMail($code, self::TTL_MINUTES));
        } catch (Throwable $e) {
            report($e);

            // Tidak terkirim: buang kodenya supaya pengguna boleh langsung mencoba lagi.
            Cache::forget(self::key($email));

            return false;
        }

        return true;
    }

    /** Cek kode. Mengembalikan pesan error, atau null kalau kodenya benar. */
    public static function check(string $email, string $code): ?string
    {
        $key = self::key($email);
        $data = Cache::get($key);

        if (! $data || $data['expires_at'] <= now()->timestamp) {
            return 'Kode belum dikirim atau sudah kedaluwarsa. Klik "Kirim kode" untuk meminta yang baru.';
        }

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            return 'Terlalu banyak percobaan. Klik "Kirim kode" untuk meminta kode baru.';
        }

        if (! Hash::check($code, $data['hash'])) {
            $data['attempts']++;
            Cache::put($key, $data, Carbon::createFromTimestamp($data['expires_at']));

            $left = self::MAX_ATTEMPTS - $data['attempts'];

            return $left > 0
                ? "Kode salah. Sisa percobaan: {$left}."
                : 'Kode salah. Klik "Kirim kode" untuk meminta kode baru.';
        }

        return null;
    }

    /** Buang kode setelah dipakai (sekali pakai). */
    public static function forget(string $email): void
    {
        Cache::forget(self::key($email));
    }
}
