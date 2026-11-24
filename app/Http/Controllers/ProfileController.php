<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('account.profile', [
            'user' => $user,
            'ordersCount' => $user->orders()->count(),
        ]);
    }

    /** Ganti nama pengguna; hanya boleh sekali dalam User::NAME_CHANGE_COOLDOWN_DAYS hari. */
    public function updateName(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ], [], ['name' => 'nama pengguna']);

        if ($availableAt = $user->nameChangeAvailableAt()) {
            return redirect()->route('profile')->with(
                'error',
                'Nama pengguna baru bisa diganti lagi pada '.$availableAt->translatedFormat('d F Y, H:i').' WIB.'
            );
        }

        // Nama yang sama tidak dihitung sebagai penggantian, jadi tidak memulai jeda.
        if ($data['name'] === $user->name) {
            return redirect()->route('profile')->with('error', 'Nama pengguna tidak berubah.');
        }

        // name_changed_at sengaja tidak fillable: hanya diisi oleh alur ini.
        $user->forceFill(['name' => $data['name'], 'name_changed_at' => now()])->save();

        return redirect()->route('profile')->with(
            'success',
            'Nama pengguna berhasil diubah. Kamu bisa menggantinya lagi setelah '.User::NAME_CHANGE_COOLDOWN_DAYS.' hari.'
        );
    }

    /** Akun Google belum punya password: izinkan membuatnya (tanpa password lama) supaya bisa masuk dengan email + password. */
    public function createPassword(Request $request)
    {
        $user = $request->user();

        // Akun yang sudah punya password wajib lewat form "Ubah password" (yang meminta password lama).
        if ($user->password !== null) {
            return redirect()->route('profile')->with('error', 'Akun ini sudah punya password. Gunakan form ubah password.');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('profile')->with('success', 'Password berhasil dibuat. Sekarang kamu juga bisa masuk dengan email dan password.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('profile')->with('success', 'Password berhasil diubah.');
    }
}
