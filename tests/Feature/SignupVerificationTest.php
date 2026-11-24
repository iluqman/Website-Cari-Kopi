<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Support\SignupVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class SignupVerificationTest extends TestCase
{
    use CreatesShopData, RefreshDatabase;

    private const EMAIL = 'budi@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        // Tes tidak boleh bergantung pada internet: matikan pengecekan DNS email.
        config(['app.check_email_dns' => false]);
    }

    /** Minta kode lewat endpoint, lalu ambil kodenya dari email yang "terkirim". */
    private function requestCode(string $email = self::EMAIL): string
    {
        Mail::fake();

        $this->postJson('/register/code', ['email' => $email])->assertOk();

        $code = null;
        Mail::assertSent(VerificationCodeMail::class, function ($mail) use (&$code, $email) {
            $code = $mail->code;

            return $mail->hasTo($email);
        });

        return $code;
    }

    private function wrongCodeFor(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    private function registerPayload(array $override = []): array
    {
        return array_merge([
            'name' => 'Budi',
            'email' => self::EMAIL,
            'password' => 'Rahasia-123!',
            'password_confirmation' => 'Rahasia-123!',
        ], $override);
    }

    public function test_a_valid_new_email_receives_a_code(): void
    {
        $code = $this->requestCode();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_a_registered_email_is_rejected_without_sending_anything(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->postJson('/register/code', ['email' => $user->email])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Mail::assertNothingSent();
    }

    public function test_an_invalid_email_is_rejected(): void
    {
        Mail::fake();

        $this->postJson('/register/code', ['email' => 'bukan-email'])->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('/register/code', [])->assertStatus(422)->assertJsonValidationErrors('email');

        Mail::assertNothingSent();
    }

    public function test_requesting_a_code_again_is_blocked_during_the_cooldown(): void
    {
        Mail::fake();

        $this->postJson('/register/code', ['email' => self::EMAIL])->assertOk()->assertJsonPath('resend_in', SignupVerification::RESEND_SECONDS);
        $this->postJson('/register/code', ['email' => self::EMAIL])->assertStatus(429)->assertJsonStructure(['message', 'resend_in']);

        Mail::assertSent(VerificationCodeMail::class, 1);
    }

    public function test_registration_succeeds_with_the_right_code(): void
    {
        $code = $this->requestCode();

        $this->post('/register', $this->registerPayload(['code' => $code]))
            ->assertRedirect('/login')
            ->assertSessionHas('success');

        $user = User::where('email', self::EMAIL)->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Rahasia-123!', $user->password));
    }

    public function test_a_code_can_only_be_used_once(): void
    {
        $code = $this->requestCode();
        $this->post('/register', $this->registerPayload(['code' => $code]));

        $this->assertNotNull(SignupVerification::check(self::EMAIL, $code));
    }

    public function test_registration_fails_with_a_wrong_code(): void
    {
        $code = $this->requestCode();

        $this->post('/register', $this->registerPayload(['code' => $this->wrongCodeFor($code)]))
            ->assertSessionHasErrors('code');

        $this->assertSame(0, User::count());
    }

    public function test_registration_fails_when_no_code_was_requested(): void
    {
        $this->post('/register', $this->registerPayload(['code' => '123456']))->assertSessionHasErrors('code');

        $this->assertSame(0, User::count());
    }

    public function test_the_code_field_is_required_and_must_be_six_digits(): void
    {
        $this->post('/register', $this->registerPayload())->assertSessionHasErrors('code');
        $this->post('/register', $this->registerPayload(['code' => 'abcdef']))->assertSessionHasErrors('code');
        $this->post('/register', $this->registerPayload(['code' => '12345']))->assertSessionHasErrors('code');

        $this->assertSame(0, User::count());
    }

    public function test_a_code_only_works_for_the_email_it_was_sent_to(): void
    {
        $code = $this->requestCode('a@example.test');

        $this->post('/register', $this->registerPayload(['email' => 'b@example.test', 'code' => $code]))
            ->assertSessionHasErrors('code');

        $this->assertSame(0, User::count());
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $code = $this->requestCode();

        $this->travel(SignupVerification::TTL_MINUTES + 1)->minutes();

        $this->post('/register', $this->registerPayload(['code' => $code]))->assertSessionHasErrors('code');

        $this->assertSame(0, User::count());
    }

    public function test_a_code_is_locked_after_too_many_wrong_attempts(): void
    {
        $code = $this->requestCode();
        $wrong = $this->wrongCodeFor($code);

        for ($i = 0; $i < SignupVerification::MAX_ATTEMPTS; $i++) {
            $this->assertNotNull(SignupVerification::check(self::EMAIL, $wrong));
        }

        // Kode yang benar pun ditolak sampai kode baru diminta.
        $this->assertStringContainsString('Terlalu banyak', SignupVerification::check(self::EMAIL, $code));
    }
}
