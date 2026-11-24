<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun awal. GANTI passwordnya setelah login pertama.
        $this->account('admin@carikopi.test', 'Admin Web', User::ROLE_ADMIN);
        $this->account('toko@carikopi.test', 'Admin Toko', User::ROLE_OWNER);
    }

    private function account(string $email, string $name, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')]
        );
        // Akun bawaan memakai email contoh, jadi langsung dianggap terverifikasi.
        $user->forceFill(['role' => $role, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
    }
}