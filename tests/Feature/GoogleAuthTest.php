<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use CreatesShopData, RefreshDatabase;

    private function fakeGoogle(array $attributes = []): void
    {
        Socialite::fake('google', SocialiteUser::fake(array_merge([
            'id' => 'google-123',
            'name' => 'Budi Google',
            'email' => 'budi@example.test',
            'email_verified' => true,
        ], $attributes)));
    }

    public function test_a_new_google_user_gets_a_verified_customer_account(): void
    {
        $this->fakeGoogle();

        $this->get('/auth/google/callback')->assertRedirect(route('home'));

        $user = User::where('email', 'budi@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
    }

    public function test_google_login_links_an_existing_verified_account_and_keeps_its_password(): void
    {
        $existing = $this->makeUser(User::ROLE_OWNER);
        $this->fakeGoogle(['email' => $existing->email]);

        $this->get('/auth/google/callback')->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($existing);
        $existing->refresh();
        $this->assertSame('google-123', $existing->google_id);
        $this->assertSame(User::ROLE_OWNER, $existing->role);
        $this->assertTrue(Hash::check('password-123', $existing->password));
    }

    public function test_linking_an_unverified_legacy_account_wipes_its_password(): void
    {
        // Akun lama yang dibuat tanpa bukti kepemilikan email: password-nya tidak boleh dipercaya.
        $existing = $this->makeUser();
        $existing->forceFill(['email_verified_at' => null])->save();
        $this->fakeGoogle(['email' => $existing->email]);

        $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($existing);
        $existing->refresh();
        $this->assertNull($existing->password);
        $this->assertNotNull($existing->email_verified_at);
    }

    public function test_an_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogle(['email_verified' => false]);

        $this->get('/auth/google/callback')->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_an_account_without_a_password_cannot_log_in_with_one(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['password' => null, 'google_id' => 'google-123'])->save();

        foreach (['apa-saja', 'password-123'] as $password) {
            $this->post('/login', ['email' => $user->email, 'password' => $password]);
            $this->assertGuest();
        }
    }
}
