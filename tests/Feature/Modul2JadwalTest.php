<?php

namespace Tests\Feature;

use App\Models\AvailabilitySlot;
use App\Models\CounselingSession;
use App\Models\ListenerProfile;
use App\Models\User;
use App\Services\ListenerMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Modul2JadwalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        // Fixed clock so "tomorrow 10:00" never crosses midnight or a past time.
        Carbon::setTestNow(Carbon::parse('2026-11-24 09:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function listener(): User
    {
        return User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
    }

    private function slotForm(string $start, string $end, string $date = '2026-11-25'): array
    {
        return ['date' => $date, 'start_time' => $start, 'end_time' => $end];
    }

    public function test_listener_can_add_a_slot(): void
    {
        $listener = $this->listener();

        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('10:00', '11:00'))
            ->assertRedirect(route('listener.slots.index'))
            ->assertSessionHasNoErrors();

        $slot = $listener->availabilitySlots()->sole();
        $this->assertTrue($slot->starts_at->equalTo(Carbon::parse('2026-11-25 10:00')));
        $this->assertTrue($slot->ends_at->equalTo(Carbon::parse('2026-11-25 11:00')));
    }

    public function test_overlapping_slot_is_rejected(): void
    {
        $listener = $this->listener();
        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('10:00', '11:00'));

        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('10:30', '11:30'))
            ->assertSessionHasErrors('start_time');
        // Touching edges are fine: 11:00-12:00 starts exactly when the first one ends.
        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('11:00', '12:00'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $listener->availabilitySlots()->count());
    }

    public function test_slot_duration_must_be_between_30_and_120_minutes(): void
    {
        $listener = $this->listener();

        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('10:00', '10:20'))
            ->assertSessionHasErrors('end_time');
        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('10:00', '12:30'))
            ->assertSessionHasErrors('end_time');
        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('11:00', '10:00'))
            ->assertSessionHasErrors('end_time');

        $this->assertSame(0, AvailabilitySlot::count());
    }

    public function test_slot_in_the_past_is_rejected(): void
    {
        $listener = $this->listener();

        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('08:00', '09:00', '2026-11-24'))
            ->assertSessionHasErrors('start_time');
        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('10:00', '11:00', '2026-11-23'))
            ->assertSessionHasErrors('date');
    }

    public function test_booked_slot_cannot_be_deleted_but_empty_one_can(): void
    {
        $listener = $this->listener();
        $booked = $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $empty = $listener->availabilitySlots()->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        app(ListenerMatcher::class)->book(User::factory()->age(20)->create(), 'cerita', now());

        $this->actingAs($listener)->delete(route('listener.slots.destroy', $booked))->assertRedirect();
        $this->actingAs($listener)->delete(route('listener.slots.destroy', $empty))->assertRedirect();

        $this->assertModelExists($booked);
        $this->assertModelMissing($empty);
    }

    public function test_slot_of_a_cancelled_session_can_be_deleted(): void
    {
        $listener = $this->listener();
        $slot = $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $session = app(ListenerMatcher::class)->book(User::factory()->age(20)->create(), 'cerita', now());

        $this->actingAs($session->user)->post(route('sessions.cancel', $session));
        $this->actingAs($listener)->delete(route('listener.slots.destroy', $slot));

        $this->assertModelMissing($slot);
    }

    public function test_listener_cannot_delete_another_listeners_slot(): void
    {
        $slot = $this->listener()->availabilitySlots()->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);

        $this->actingAs($this->listener())->delete(route('listener.slots.destroy', $slot))->assertForbidden();
        $this->assertModelExists($slot);
    }

    public function test_only_listeners_can_open_the_schedule_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('listener.slots.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('listener.slots.index'))->assertForbidden();
    }

    public function test_schedule_page_marks_booked_slots_and_warns_unverified_listeners(): void
    {
        $listener = $this->listener();
        $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $user = User::factory()->age(20)->create(['name' => 'Bima']);
        app(ListenerMatcher::class)->book($user, 'akademik', now());

        $this->actingAs($listener)->get(route('listener.slots.index'))
            ->assertOk()
            ->assertSee('Dibooking')
            ->assertSee('Bima')
            ->assertSee(CounselingSession::TOPICS['akademik'])
            ->assertDontSee('Slot baru dipakai setelah akunmu diverifikasi admin.');

        $pending = User::factory()->age(21)->listener()->create();
        $this->actingAs($pending)->get(route('listener.slots.index'))
            ->assertSee('Slot baru dipakai setelah akunmu diverifikasi admin.');
    }

    public function test_new_slot_is_used_by_matching(): void
    {
        $listener = $this->listener();
        $this->actingAs($listener)->post(route('listener.slots.store'), $this->slotForm('10:00', '11:00'));
        $user = User::factory()->age(20)->create();

        $this->actingAs($user)->post(route('booking.store'), ['topic' => 'cerita', 'scheduled_at' => '2026-11-25 10:30'])
            ->assertRedirect(route('sessions.index'));

        $session = CounselingSession::sole();
        $this->assertSame($listener->id, $session->listener_id);
        $this->actingAs($user)->get(route('sessions.index'))->assertSee($listener->name);
        $this->actingAs($listener)->get(route('sessions.index'))->assertSee($user->name);
    }

    public function test_sessions_page_splits_upcoming_and_history(): void
    {
        $listener = $this->listener();
        $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        $user = User::factory()->age(20)->create();
        $session = app(ListenerMatcher::class)->book($user, 'cerita', now());

        $this->actingAs($user)->get(route('sessions.index'))
            ->assertOk()
            ->assertSeeInOrder(['Akan datang', 'Relawan terverifikasi', 'Terjadwal', 'Batalkan', 'Riwayat', 'Belum ada riwayat sesi.']);

        $session->forceFill(['status' => CounselingSession::ENDED])->save();

        $this->actingAs($user)->get(route('sessions.index'))
            ->assertSeeInOrder(['Akan datang', 'Tidak ada sesi yang akan datang.', 'Riwayat', 'Selesai'])
            ->assertDontSee('Batalkan</button>', false);
    }

    public function test_failed_booking_suggests_the_nearest_open_times(): void
    {
        $listener = $this->listener();
        foreach ([1, 2, 3, 4] as $day) {
            $start = now()->addDays($day)->setTime(19, 0);
            $listener->availabilitySlots()->create(['starts_at' => $start, 'ends_at' => $start->copy()->addHour()]);
        }
        $user = User::factory()->age(20)->create();

        $this->actingAs($user)->post(route('booking.store'), ['topic' => 'cerita', 'scheduled_at' => '2026-11-25 08:00'])
            ->assertSessionHas('suggestions', ['2026-11-25 19:00', '2026-11-26 19:00', '2026-11-27 19:00']);
    }

    public function test_suggestions_follow_the_matching_rules(): void
    {
        // A teen is only matched with psychologists, so a volunteer's slot is no suggestion.
        $start = now()->addDay()->setTime(19, 0);
        $this->listener()->availabilitySlots()->create(['starts_at' => $start, 'ends_at' => $start->copy()->addHour()]);

        $this->actingAs(User::factory()->age(15)->create())
            ->post(route('booking.store'), ['topic' => 'cerita', 'scheduled_at' => '2026-11-25 08:00'])
            ->assertSessionHas('suggestions', []);
    }

    public function test_landing_and_home_pages_render_for_every_role(): void
    {
        $this->get('/')->assertOk()->assertSee('Cara kerjanya')->assertSee('bukan pengganti terapi');

        foreach ([User::factory()->age(20)->create(), $this->listener(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('Bantuan darurat');
        }
    }
}
