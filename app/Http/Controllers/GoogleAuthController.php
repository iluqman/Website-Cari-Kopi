<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Masuk dengan Google gagal. Silakan coba lagi.');
        }

        // Jangan percaya email yang belum diverifikasi Google.
        if (! $google->getEmail() || ! data_get($google->user, 'email_verified', false)) {
            return redirect()->route('login')->with('error', 'Email Google belum terverifikasi.');
        }

        $email = Str::lower($google->getEmail());

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $email)->first()
            ?? new User(['name' => Str::limit($google->getName() ?: $email, 100, ''), 'email' => $email]);

        // forceFill: google_id & email_verified_at sengaja tidak fillable (sama seperti role/member).
        $user->forceFill([
            'google_id' => $google->getId(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended($user->canManageShop() ? route('dashboard') : route('home'));
    }
}