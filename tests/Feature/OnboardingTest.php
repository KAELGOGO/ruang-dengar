<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function registration(string $as): array
    {
        return ['name' => 'Rina', 'email' => "rina-$as@x.test", 'birth_date' => now()->subYears(20)->toDateString(),
            'register_as' => $as, 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'];
    }

    public function test_new_users_meet_dengi_but_listeners_go_home(): void
    {
        $this->post('/register', $this->registration('user'))->assertRedirect(route('onboarding.show'));
        $this->post('/logout');
        $this->post('/register', $this->registration('listener'))->assertRedirect('/home');
    }

    public function test_onboarding_saves_the_answers(): void
    {
        $user = User::factory()->age(20)->create();

        $this->actingAs($user)->get(route('onboarding.show'))->assertOk()->assertSee('Aku Dengi', false);
        $this->actingAs($user)->post(route('onboarding.store'), [
            'nickname' => 'Rin', 'mood' => 'cemas', 'preferred_topic' => 'akademik',
        ])->assertRedirect(route('home'));

        $user->refresh();
        $this->assertSame('Rin', $user->nickname);
        $this->assertSame('cemas', $user->mood);
        $this->assertSame('akademik', $user->preferred_topic);
        $this->assertFalse($user->needsOnboarding());

        $this->actingAs($user)->get(route('home'))->assertSee('Hai Rin!')->assertDontSee('Kenalan dulu sama Dengi');
        // The booking form starts on the topic picked during onboarding.
        $html = $this->actingAs($user)->get(route('booking.create'))->getContent();
        $this->assertMatchesRegularExpression('/value="akademik"\s+checked/', $html);
    }

    public function test_skipping_counts_as_done(): void
    {
        $user = User::factory()->age(20)->create();

        $this->actingAs($user)->get(route('home'))->assertSee('Kenalan dulu sama Dengi');
        $this->actingAs($user)->post(route('onboarding.store'))->assertRedirect(route('home'));

        $this->assertFalse($user->refresh()->needsOnboarding());
    }

    public function test_invalid_answers_are_rejected(): void
    {
        $user = User::factory()->age(20)->create();

        $this->actingAs($user)->post(route('onboarding.store'), ['mood' => 'marah', 'preferred_topic' => 'x', 'nickname' => str_repeat('a', 41)])
            ->assertSessionHasErrors(['mood', 'preferred_topic', 'nickname']);
        $this->assertTrue($user->refresh()->needsOnboarding());
    }

    public function test_only_users_have_onboarding(): void
    {
        $this->actingAs(User::factory()->listener()->create())->get(route('onboarding.show'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post(route('onboarding.store'))->assertForbidden();
    }
}
