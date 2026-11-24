<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use CreatesShopData, RefreshDatabase;

    /** Akun yang dibuat lewat Google: tidak punya password. */
    private function makeGoogleUser(): User
    {
        $user = $this->makeUser();
        $user->forceFill(['password' => null, 'google_id' => 'google-123'])->save();

        return $user;
    }

    private function newPassword(array $override = []): array
    {
        return array_merge(['password' => 'Password-Baru-456!', 'password_confirmation' => 'Password-Baru-456!'], $override);
    }

    public function test_a_google_account_sees_the_create_password_form(): void
    {
        $this->actingAs($this->makeGoogleUser())->get('/profile')
            ->assertOk()
            ->assertSee('Buat password')
            ->assertDontSee('Password saat ini');
    }

    public function test_an_account_with_a_password_sees_the_change_form_instead(): void
    {
        $this->actingAs($this->makeUser())->get('/profile')
            ->assertOk()
            ->assertSee('Password saat ini')
            ->assertDontSee('Buat password');
    }

    public function test_a_google_account_can_create_a_password_and_then_log_in_with_it(): void
    {
        $user = $this->makeGoogleUser();

        $this->actingAs($user)
            ->post('/password', $this->newPassword())
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('Password-Baru-456!', $user->fresh()->password));

        // Setelah password ada, form di profil berganti menjadi "Ubah password".
        $this->actingAs($user->fresh())->get('/profile')
            ->assertSee('Password saat ini')
            ->assertDontSee('Buat password');

        // Dan akun itu bisa masuk dengan email + password.
        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'Password-Baru-456!'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_new_password_is_validated(): void
    {
        $user = $this->makeGoogleUser();

        foreach ([
            $this->newPassword(['password' => 'pendek', 'password_confirmation' => 'pendek']),
            $this->newPassword(['password_confirmation' => 'beda-sekali']),
            [],
        ] as $payload) {
            $this->actingAs($user)->post('/password', $payload)->assertSessionHasErrors('password');
        }

        $this->assertNull($user->fresh()->password);
    }

    public function test_an_account_that_already_has_a_password_cannot_use_the_create_endpoint(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/password', $this->newPassword())
            ->assertRedirect(route('profile'))
            ->assertSessionHas('error');

        // Password lama tidak berubah (mengubahnya harus lewat form yang meminta password saat ini).
        $this->assertTrue(Hash::check('password-123', $user->fresh()->password));
    }

    public function test_guests_cannot_create_a_password(): void
    {
        $this->post('/password', $this->newPassword())->assertRedirect('/login');
    }
}
