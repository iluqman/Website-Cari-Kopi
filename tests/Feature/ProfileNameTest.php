<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

class ProfileNameTest extends TestCase
{
    use CreatesShopData, RefreshDatabase;

    public function test_the_name_can_be_changed_and_starts_the_cooldown(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->patch('/profile/name', ['name' => 'Nama Baru'])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertNotNull($user->name_changed_at);
        $this->assertFalse($user->canChangeName());
    }

    public function test_a_second_change_during_the_cooldown_is_rejected(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->patch('/profile/name', ['name' => 'Nama Pertama']);

        $this->travel(User::NAME_CHANGE_COOLDOWN_DAYS - 1)->days();

        $this->actingAs($user->fresh())
            ->patch('/profile/name', ['name' => 'Nama Kedua'])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('error');

        $this->assertSame('Nama Pertama', $user->fresh()->name);
    }

    public function test_the_name_can_be_changed_again_after_the_cooldown(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->patch('/profile/name', ['name' => 'Nama Pertama']);

        $this->travel(User::NAME_CHANGE_COOLDOWN_DAYS)->days();

        $this->actingAs($user->fresh())
            ->patch('/profile/name', ['name' => 'Nama Kedua'])
            ->assertSessionHas('success');

        $this->assertSame('Nama Kedua', $user->fresh()->name);
    }

    public function test_submitting_the_same_name_does_not_start_the_cooldown(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->patch('/profile/name', ['name' => $user->name])
            ->assertSessionHas('error');

        $this->assertNull($user->fresh()->name_changed_at);
        $this->assertTrue($user->fresh()->canChangeName());
    }

    public function test_the_name_is_validated(): void
    {
        $user = $this->makeUser();

        foreach (['', 'a', str_repeat('a', 101)] as $name) {
            $this->actingAs($user)->patch('/profile/name', ['name' => $name])->assertSessionHasErrors('name');
        }

        $this->assertNull($user->fresh()->name_changed_at);
    }

    public function test_guests_cannot_change_a_name(): void
    {
        $this->patch('/profile/name', ['name' => 'Siapa Saja'])->assertRedirect('/login');
    }

    public function test_the_profile_shows_the_form_or_the_cooldown_message(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Simpan nama')
            ->assertDontSee('menggantinya lagi pada');

        $this->actingAs($user)->patch('/profile/name', ['name' => 'Nama Baru']);

        $this->actingAs($user->fresh())->get('/profile')
            ->assertOk()
            ->assertSee('menggantinya lagi pada');
    }

    public function test_the_cooldown_date_is_exactly_seven_days_after_the_change(): void
    {
        $this->freezeTime();

        $user = $this->makeUser();
        $user->forceFill(['name_changed_at' => now()->subDays(2)])->save();

        $this->assertEquals(
            now()->subDays(2)->addDays(7)->timestamp,
            $user->fresh()->nameChangeAvailableAt()->timestamp
        );
    }
}
