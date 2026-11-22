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

    public function test_peers_of_the_same_tier_come_first_then_other_tiers(): void
    {
        $adultVolunteer = User::factory()->age(30)->listener(ListenerProfile::VOLUNTEER)->create();
        $youngStudent = User::factory()->age(21)->listener(ListenerProfile::PSYCH_STUDENT)->create();
        $this->slotNow($adultVolunteer);
        $this->slotNow($youngStudent);

        $this->assertSame($youngStudent->id, $this->book(User::factory()->age(20)->create()));
        // No same-tier peer left: an older peer is better than nobody.
        $this->assertSame($adultVolunteer->id, $this->book(User::factory()->age(22)->create()));
    }

    public function test_same_tier_beats_load_among_peers(): void
    {
        $samePeer = User::factory()->age(21)->listener(ListenerProfile::VOLUNTEER)->create();
        $otherPeer = User::factory()->age(30)->listener(ListenerProfile::VOLUNTEER)->create();
        $this->slotNow($samePeer);
        $this->slotNow($samePeer);
        $this->slotNow($otherPeer);
        $this->book(User::factory()->age(20)->create()); // the same-tier peer now has 1 session, the other 0

        $this->assertSame($samePeer->id, $this->book(User::factory()->age(20)->create()));
    }

    public function test_a_listener_joins_the_new_tier_on_their_birthday(): void
    {
        $turns25Today = User::factory()->listener(ListenerProfile::VOLUNTEER)
            ->create(['birth_date' => now()->subYears(25)->toDateString()]);
        $young = User::factory()->age(22)->listener(ListenerProfile::VOLUNTEER)->create();
        // The young peer's slot is earlier, so it would win if the birthday were missed.
        $young->availabilitySlots()->create(['starts_at' => now()->subHours(2), 'ends_at' => now()->addHour()]);
        $this->slotNow($turns25Today);

        $this->assertSame($turns25Today->id, $this->book(User::factory()->age(30)->create()));
    }

    public function test_psychologists_are_ordered_by_load_only(): void
    {
        $youngPsychologist = User::factory()->age(24)->listener(ListenerProfile::PSYCHOLOGIST)->create();
        $olderPsychologist = User::factory()->age(40)->listener(ListenerProfile::PSYCHOLOGIST)->create();
        // Earliest slot, so the teen below deterministically gets the young psychologist.
        $youngPsychologist->availabilitySlots()->create(['starts_at' => now()->subHours(2), 'ends_at' => now()->addHour()]);
        $this->slotNow($youngPsychologist);
        $this->slotNow($olderPsychologist);
        $this->book(User::factory()->age(15)->create());

        // Same tier as a 20-year-old but busier: the tier does not rank psychologists.
        $this->assertSame($olderPsychologist->id, $this->book(User::factory()->age(20)->create()));
    }

    public function test_psychologists_are_the_last_resort_for_adults(): void
    {
        $psychologist = User::factory()->age(45)->listener(ListenerProfile::PSYCHOLOGIST)->create();
        $youngVolunteer = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $this->slotNow($psychologist);
        $this->slotNow($youngVolunteer);
        $this->slotNow($youngVolunteer);
        $this->book(User::factory()->age(20)->create()); // the volunteer is now busier than the psychologist

        // Still a peer first, even from another tier and with more sessions.
        $this->assertSame($youngVolunteer->id, $this->book(User::factory()->age(40)->create()));
        // No peer left: now the psychologist.
        $this->assertSame($psychologist->id, $this->book(User::factory()->age(50)->create()));
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
