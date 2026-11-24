<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

/** Aturan password: minimal 8 karakter, ada huruf besar, huruf kecil, angka, dan simbol. */
class PasswordStrengthTest extends TestCase
{
    use CreatesShopData, RefreshDatabase;

    private const STRONG = 'Kopi-Enak-2026!';

    protected function setUp(): void
    {
        parent::setUp();

        // Tes tidak boleh bergantung pada internet: matikan pengecekan DNS email.
        config(['app.check_email_dns' => false]);
    }

    /** @return array<string, string> alasan => password yang melanggarnya */
    private function weakPasswords(): array
    {
        return [
            'tanpa huruf besar' => 'abcdefg1!',
            'tanpa huruf kecil' => 'ABCDEFG1!',
            'tanpa angka' => 'Abcdefgh!',
            'tanpa simbol' => 'Abcdefg12',
            'kurang dari 8 karakter' => 'Abc12!x',
        ];
    }

    private function asGoogleUser(): User
    {
        $user = $this->makeUser();
        $user->forceFill(['password' => null, 'google_id' => 'google-123'])->save();

        return $user;
    }

    // Rute daftar dibatasi 5 permintaan/menit, jadi tes "lemah" (5 kasus) dan "kuat" dipisah
    // supaya permintaan ke-6 tidak terkena batas dan lolos secara semu.
    public function test_registration_rejects_weak_passwords(): void
    {
        foreach ($this->weakPasswords() as $password) {
            $this->post('/register', [
                'name' => 'Budi', 'email' => 'budi@example.test', 'code' => '123456',
                'password' => $password, 'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');
        }
    }

    public function test_registration_accepts_a_strong_password(): void
    {
        // Password kuat lolos validasi; satu-satunya error yang tersisa adalah kode verifikasi
        // (kode belum diminta), yang dicek setelah validasi password.
        $this->post('/register', [
            'name' => 'Budi', 'email' => 'budi@example.test', 'code' => '123456',
            'password' => self::STRONG, 'password_confirmation' => self::STRONG,
        ])->assertSessionHasErrors('code')->assertSessionDoesntHaveErrors('password');
    }

    // Rute buat-password juga dibatasi 5 permintaan/menit (lihat catatan di atas).
    public function test_creating_a_password_on_the_profile_rejects_weak_ones(): void
    {
        $user = $this->asGoogleUser();

        foreach ($this->weakPasswords() as $password) {
            $this->actingAs($user)
                ->post('/password', ['password' => $password, 'password_confirmation' => $password])
                ->assertSessionHasErrors('password');
        }

        $this->assertNull($user->fresh()->password);
    }

    public function test_creating_a_password_on_the_profile_accepts_a_strong_one(): void
    {
        $user = $this->asGoogleUser();

        $this->actingAs($user)
            ->post('/password', ['password' => self::STRONG, 'password_confirmation' => self::STRONG])
            ->assertRedirect(route('profile'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check(self::STRONG, $user->fresh()->password));
    }

    public function test_changing_a_password_on_the_profile_requires_a_strong_one(): void
    {
        $user = $this->makeUser();

        foreach ($this->weakPasswords() as $password) {
            $this->actingAs($user)
                ->patch('/password', [
                    'current_password' => 'password-123',
                    'password' => $password,
                    'password_confirmation' => $password,
                ])
                ->assertSessionHasErrors('password');
        }

        $this->assertTrue(Hash::check('password-123', $user->fresh()->password));

        $this->actingAs($user)
            ->patch('/password', [
                'current_password' => 'password-123',
                'password' => self::STRONG,
                'password_confirmation' => self::STRONG,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check(self::STRONG, $user->fresh()->password));
    }

    public function test_the_error_messages_are_in_indonesian(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->patch('/password', [
                'current_password' => 'password-123',
                'password' => 'abcdefg1!',
                'password_confirmation' => 'abcdefg1!',
            ])
            ->assertSessionHasErrors(['password' => 'Password harus mengandung minimal satu huruf besar dan satu huruf kecil.']);
    }
}
