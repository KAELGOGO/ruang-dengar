<?php

namespace Tests\Feature;

use App\Models\CounselingSession;
use App\Models\ListenerProfile;
use App\Models\User;
use App\Services\ListenerMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function bookedSession(): CounselingSession
    {
        $listener = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

        return app(ListenerMatcher::class)->book(User::factory()->age(20)->create(), 'cerita', now());
    }

    public function test_registration_sets_the_role_but_never_admin(): void
    {
        $data = ['name' => 'A', 'email' => 'a@x.test', 'birth_date' => now()->subYears(20)->toDateString(),
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'];

        $this->post('/register', $data + ['register_as' => 'admin'])->assertSessionHasErrors('register_as');
        $this->post('/register', $data + ['register_as' => 'listener', 'role' => 'admin'])->assertRedirect('/home');

        $user = User::where('email', 'a@x.test')->first();
        $this->assertSame(User::ROLE_LISTENER, $user->role);
        $this->assertSame(ListenerProfile::PENDING, $user->listenerProfile->verification_status);
    }

    public function test_age_limits_on_registration(): void
    {
        $data = ['name' => 'B', 'email' => 'b@x.test', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'];

        $this->post('/register', $data + ['register_as' => 'user', 'birth_date' => now()->subYears(12)->toDateString()])
            ->assertSessionHasErrors('birth_date');
        $this->post('/register', $data + ['register_as' => 'listener', 'birth_date' => now()->subYears(16)->toDateString()])
            ->assertSessionHasErrors('register_as');
    }

    public function test_role_areas_are_closed_to_other_roles(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/metrics')->assertForbidden();
        $this->actingAs($user)->get('/listener/slots')->assertForbidden();
        $this->actingAs($user)->get('/escalations')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/metrics')->assertOk();
    }

    public function test_only_participants_can_open_a_chat(): void
    {
        $session = $this->bookedSession();

        $this->actingAs($session->user)->get(route('chat.show', $session))->assertOk();
        $this->actingAs($session->listener)->get(route('chat.show', $session))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('chat.show', $session))->assertForbidden();
        // Admins see counts in the metrics, never the chat itself.
        $this->actingAs(User::factory()->admin()->create())->get(route('chat.show', $session))->assertForbidden();
    }

    public function test_booking_now_creates_a_session(): void
    {
        $listener = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $user = User::factory()->age(21)->create();

        $this->actingAs($user)->post('/booking', ['topic' => 'cerita', 'now' => 1])->assertRedirect(route('sessions.index'));
        $this->assertSame($listener->id, $user->sessionsAsUser()->first()->listener_id);
    }
}
