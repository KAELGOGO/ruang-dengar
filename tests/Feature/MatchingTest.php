<?php

namespace Tests\Feature;

use App\Models\AvailabilitySlot;
use App\Models\ListenerProfile;
use App\Models\User;
use App\Services\ListenerMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchingTest extends TestCase
{
    use RefreshDatabase;

    private function slotNow(User $listener): AvailabilitySlot
    {
        return $listener->availabilitySlots()->create([
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);
    }

    private function book(User $user): ?int
    {
        return app(ListenerMatcher::class)->book($user, 'cerita', now())?->listener_id;
    }

    public function test_teens_are_only_matched_with_psychologists(): void
    {
        $teen = User::factory()->age(15)->create();
        $volunteer = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $psychologist = User::factory()->age(40)->listener(ListenerProfile::PSYCHOLOGIST)->create();
        $this->slotNow($volunteer);
        $this->slotNow($psychologist);

        $this->assertSame($psychologist->id, $this->book($teen));
    }

    public function test_peers_serve_their_own_tier_only(): void
    {
        $young = User::factory()->age(20)->create();
        $adultVolunteer = User::factory()->age(30)->listener(ListenerProfile::VOLUNTEER)->create();
        $this->slotNow($adultVolunteer);
        $this->assertNull($this->book($young));

        $youngStudent = User::factory()->age(21)->listener(ListenerProfile::PSYCH_STUDENT)->create();
        $this->slotNow($youngStudent);
        $this->assertSame($youngStudent->id, $this->book($young));
    }

    public function test_unverified_listeners_are_never_matched(): void
    {
        $user = User::factory()->age(20)->create();
        $pending = User::factory()->age(20)->listener()->create(); // no credential = not approved
        $this->slotNow($pending);

        $this->assertNull($this->book($user));
    }

    public function test_a_slot_is_booked_only_once(): void
    {
        $listener = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $this->slotNow($listener);

        $this->assertSame($listener->id, $this->book(User::factory()->age(20)->create()));
        $this->assertNull($this->book(User::factory()->age(22)->create()));
    }

    public function test_the_least_busy_listener_goes_first(): void
    {
        $busy = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $free = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $this->slotNow($busy);
        $this->assertSame($busy->id, $this->book(User::factory()->age(20)->create()));

        $this->slotNow($busy);
        $this->slotNow($free);
        $this->assertSame($free->id, $this->book(User::factory()->age(20)->create()));
    }

    public function test_listeners_cannot_book_themselves(): void
    {
        $listener = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $this->slotNow($listener);

        $this->assertNull($this->book($listener));
    }
}
