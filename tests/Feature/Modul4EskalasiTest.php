<?php

namespace Tests\Feature;

use App\Models\CounselingSession;
use App\Models\Escalation;
use App\Models\ListenerProfile;
use App\Models\Message;
use App\Models\User;
use App\Services\Escalator;
use App\Services\ListenerMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Modul4EskalasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** User (20) booked with a volunteer whose slot is running now. */
    private function bookedSession(): CounselingSession
    {
        $listener = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

        return app(ListenerMatcher::class)->book(User::factory()->age(20)->create(), 'cerita', now());
    }

    private function psychologist(bool $onDutyNow = false): User
    {
        $psychologist = User::factory()->age(35)->listener(ListenerProfile::PSYCHOLOGIST)->create();
        if ($onDutyNow) {
            $psychologist->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        }

        return $psychologist;
    }

    public function test_only_the_sessions_listener_can_escalate(): void
    {
        $session = $this->bookedSession();
        $otherListener = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();

        $this->actingAs($session->user)->post(route('escalations.store', $session), ['reason' => 'x'])->assertForbidden();
        $this->actingAs($otherListener)->post(route('escalations.store', $session), ['reason' => 'x'])->assertForbidden();
        $this->actingAs($session->listener)->post(route('escalations.store', $session), ['reason' => ''])
            ->assertSessionHasErrors('reason');
        $this->assertSame(0, Escalation::count());

        $this->actingAs($session->listener)->post(route('escalations.store', $session), ['reason' => 'Perlu psikolog'])
            ->assertRedirect()->assertSessionHas('status');
        $this->assertSame(Escalation::OPEN, $session->escalation()->first()->status);

        // Once escalated, the button is gone and a second escalation is refused.
        $this->actingAs($session->listener)->post(route('escalations.store', $session), ['reason' => 'lagi'])->assertForbidden();
        $this->assertSame(1, Escalation::count());
    }

    public function test_escalation_pairs_a_free_psychologist_in_a_new_session(): void
    {
        $session = $this->bookedSession();
        $psychologist = $this->psychologist(onDutyNow: true);

        $escalation = app(Escalator::class)->byListener($session, $session->listener, 'Perlu psikolog');

        $this->assertSame(Escalation::ASSIGNED, $escalation->status);
        $this->assertSame($psychologist->id, $escalation->psychologist_id);
        $this->assertSame('listener', $escalation->source);
        $this->assertSame($session->listener_id, $escalation->raised_by);

        $new = CounselingSession::where('listener_id', $psychologist->id)->first();
        $this->assertSame($session->user_id, $new->user_id);
        $this->assertSame($session->topic, $new->topic);
        $this->assertSame(CounselingSession::SCHEDULED, $new->status);
        $this->assertSame($new->id, $escalation->followUpSession()->id);
        // The old chat stays open so the listener can keep the user company.
        $this->assertSame(CounselingSession::SCHEDULED, $session->fresh()->status);
    }

    public function test_without_a_free_psychologist_the_case_waits_in_the_queue(): void
    {
        $session = $this->bookedSession();
        $psychologist = $this->psychologist(); // no slot now

        $escalation = app(Escalator::class)->byListener($session, $session->listener, 'Perlu psikolog');

        $this->assertSame(Escalation::OPEN, $escalation->status);
        $this->assertNull($escalation->psychologist_id);
        $this->assertSame(1, CounselingSession::count());
        $this->assertSame(CounselingSession::SCHEDULED, $session->fresh()->status);

        $this->actingAs($psychologist)->get(route('escalations.index'))
            ->assertOk()->assertSee('Perlu psikolog')->assertSee('Ambil kasus');
    }

    public function test_only_the_first_psychologist_can_take_a_case_and_only_they_resolve_it(): void
    {
        $session = $this->bookedSession();
        $escalation = app(Escalator::class)->byListener($session, $session->listener, 'Perlu psikolog');
        [$first, $second] = [$this->psychologist(), $this->psychologist()];

        // Before taking it, the psychologist may not read the old chat.
        $this->actingAs($first)->get(route('chat.show', $session))->assertForbidden();

        $response = $this->actingAs($first)->post(route('escalations.take', $escalation));
        $new = CounselingSession::where('listener_id', $first->id)->firstOrFail();
        $response->assertRedirect(route('chat.show', $new));
        $this->assertSame($session->user_id, $new->user_id);

        $this->actingAs($second)->post(route('escalations.take', $escalation))
            ->assertSessionHas('status', 'Kasus sudah diambil psikolog lain.');
        $this->assertSame(0, CounselingSession::where('listener_id', $second->id)->count());
        $this->assertSame($first->id, $escalation->fresh()->psychologist_id);

        // After taking it: old chat readable, new chat usable right now.
        $this->actingAs($first)->get(route('chat.show', $session))->assertOk();
        $this->actingAs($first)->postJson(route('chat.store', $new), ['body' => 'Halo, saya psikolog.'])->assertCreated();

        $this->actingAs($second)->post(route('escalations.resolve', $escalation))->assertForbidden();
        $this->actingAs($first)->post(route('escalations.resolve', $escalation))->assertRedirect();
        $this->assertSame(Escalation::RESOLVED, $escalation->fresh()->status);
        $this->assertNotNull($escalation->fresh()->resolved_at);
    }

    public function test_a_psychologist_cannot_take_the_case_of_their_own_session(): void
    {
        // Teens are matched with psychologists, so the escalating listener can be one.
        $psychologist = $this->psychologist(onDutyNow: true);
        $session = app(ListenerMatcher::class)->book(User::factory()->age(15)->create(), 'cerita', now());
        $this->assertSame($psychologist->id, $session->listener_id);

        // The button is for peer listeners; a psychologist is already the professional.
        $this->actingAs($psychologist)->get(route('chat.show', $session))->assertDontSee('Oper ke psikolog');
        $this->actingAs($psychologist)->post(route('escalations.store', $session), ['reason' => 'x'])->assertForbidden();

        // Even if such a case existed, its own psychologist could not take it.
        $escalation = app(Escalator::class)->byListener($session, $psychologist, 'Butuh psikolog lain');
        $this->assertSame(Escalation::OPEN, $escalation->status);

        $this->actingAs($psychologist)->post(route('escalations.take', $escalation))->assertForbidden();
        $this->assertSame(Escalation::OPEN, $escalation->fresh()->status);
    }

    public function test_rating_once_per_finished_session_with_a_valid_score(): void
    {
        $session = $this->bookedSession();
        $user = $session->user;

        // Not finished yet.
        $this->actingAs($user)->get(route('ratings.create', $session))->assertForbidden();

        $session->forceFill(['status' => CounselingSession::ENDED, 'started_at' => now(), 'ended_at' => now()])->save();

        $this->actingAs($session->listener)->get(route('ratings.create', $session))->assertForbidden();
        $this->actingAs($user)->get(route('ratings.create', $session))->assertOk()->assertSee('Kirim penilaian');
        $this->actingAs($user)->post(route('ratings.store', $session), ['score' => 6])->assertSessionHasErrors('score');

        $this->actingAs($user)->post(route('ratings.store', $session), ['score' => 5, 'comment' => 'Membantu'])
            ->assertRedirect(route('sessions.index'));
        $this->assertSame(5, $session->rating()->first()->score);

        $this->actingAs($user)->post(route('ratings.store', $session), ['score' => 4])->assertForbidden();
        $this->actingAs($user)->get(route('sessions.index'))->assertDontSee('Beri rating');
    }

    public function test_metrics_are_admin_only_and_never_show_chat_content(): void
    {
        $session = $this->bookedSession();
        $message = new Message(['body' => 'rahasia sangat pribadi', 'flagged' => true]);
        $message->sender()->associate($session->user);
        $session->messages()->save($message);
        $session->forceFill(['status' => CounselingSession::ENDED, 'started_at' => now(), 'ended_at' => now()])->save();
        $session->rating()->create(['score' => 4]);

        $this->actingAs($session->user)->get(route('admin.metrics'))->assertForbidden();
        $this->actingAs($this->psychologist())->get(route('admin.metrics'))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.metrics'))
            ->assertOk()
            ->assertViewHas('sesiSelesai', 1)
            ->assertViewHas('pesanBertanda', 1)
            ->assertViewHas('rataRating', 4.0)
            ->assertDontSee('rahasia sangat pribadi');
    }

    public function test_repeat_usage_counts_users_with_more_than_one_finished_session(): void
    {
        $first = $this->bookedSession();
        $first->forceFill(['status' => CounselingSession::ENDED, 'started_at' => now()])->save();

        $listener = User::factory()->age(21)->listener(ListenerProfile::VOLUNTEER)->create();
        $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        app(ListenerMatcher::class)->book($first->user, 'akademik', now())
            ->forceFill(['status' => CounselingSession::ENDED, 'started_at' => now()])->save();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.metrics'))
            ->assertViewHas('penggunaKembali', 1)
            ->assertViewHas('penggunaDilayani', 1);
    }
}
