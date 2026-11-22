<?php

namespace Tests\Feature;

use App\Models\CounselingSession;
use App\Models\Escalation;
use App\Models\ListenerProfile;
use App\Models\User;
use App\Services\ListenerMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** Booked now, in a slot running from an hour ago until an hour from now. */
    private function liveSession(): CounselingSession
    {
        $listener = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)->create();
        $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);

        return app(ListenerMatcher::class)->book(User::factory()->age(20)->create(), 'cerita', now());
    }

    private function send(User $sender, CounselingSession $session, string $body): TestResponse
    {
        return $this->actingAs($sender)->postJson(route('chat.store', $session), ['body' => $body]);
    }

    public function test_messages_and_ending_are_rejected_outside_the_session_hours(): void
    {
        $session = $this->liveSession();

        $this->travel(-5)->minutes(); // before scheduled_at
        $this->send($session->user, $session, 'halo')->assertForbidden();
        $this->actingAs($session->user)->post(route('chat.end', $session))->assertForbidden();

        $this->travelBack();
        $this->travel(2)->hours(); // after the slot ended
        $this->send($session->user, $session, 'halo')->assertForbidden();

        $this->assertSame(0, $session->messages()->count());
    }

    public function test_crisis_keywords_flag_any_message_but_never_escalate_by_themselves(): void
    {
        $session = $this->liveSession();

        $this->send($session->user, $session, 'Aku INGIN   mati rasanya')->assertCreated()->assertJsonPath('krisis', true);
        $this->send($session->listener, $session, 'Apakah kamu ingin mati?')->assertCreated()->assertJsonPath('krisis', true);
        $this->send($session->user, $session, 'iya kadang')->assertCreated()->assertJsonPath('krisis', false);

        $this->assertSame([true, true, false], $session->messages()->orderBy('id')->pluck('flagged')->all());
        $this->actingAs($session->listener)->getJson(route('chat.messages', $session))->assertJsonPath('pesan.0.flagged', true);
        $this->actingAs($session->user)->getJson(route('chat.messages', $session))->assertJsonPath('pesan.0.flagged', false);
        // Escalating is the listener's decision (Module 4 button), not the keyword's.
        $this->assertSame(0, Escalation::count());
        $this->assertSame(CounselingSession::ACTIVE, $session->fresh()->status);
    }

    public function test_polling_reports_the_hand_over_to_a_psychologist(): void
    {
        $session = $this->liveSession();
        $poll = fn () => $this->actingAs($session->user)->getJson(route('chat.messages', $session));

        $poll()->assertJsonPath('eskalasi', null);

        $escalation = $session->escalation()->forceCreate([
            'source' => 'listener', 'raised_by' => $session->listener_id, 'reason' => 'perlu psikolog',
        ]);
        $poll()->assertJsonPath('eskalasi', 'menunggu');

        $psychologist = User::factory()->age(35)->listener(ListenerProfile::PSYCHOLOGIST)->create();
        $escalation->forceFill(['psychologist_id' => $psychologist->id, 'status' => Escalation::ASSIGNED])->save();
        $poll()->assertJsonPath('eskalasi', 'diambil');
    }

    public function test_message_body_is_encrypted_in_the_database(): void
    {
        $session = $this->liveSession();
        $this->send($session->user, $session, 'rahasia sekali')->assertCreated();

        $this->assertNotSame('rahasia sekali', DB::table('messages')->value('body'));
        $this->assertSame('rahasia sekali', $session->messages()->first()->body);
    }

    public function test_first_message_starts_the_session_and_polling_returns_only_newer_messages(): void
    {
        $session = $this->liveSession();
        $first = $this->send($session->user, $session, 'halo')->json('pesan.id');
        $this->send($session->listener, $session, 'hai, aku di sini');

        $session->refresh();
        $this->assertSame(CounselingSession::ACTIVE, $session->status);
        $this->assertNotNull($session->started_at);

        $this->actingAs($session->user)->getJson(route('chat.messages', [$session, 'after' => $first]))
            ->assertOk()
            ->assertJsonPath('status', CounselingSession::ACTIVE)
            ->assertJsonCount(1, 'pesan')
            ->assertJsonPath('pesan.0.body', 'hai, aku di sini')
            ->assertJsonPath('pesan.0.punya_saya', false);
    }

    public function test_ending_sends_the_user_to_rating_and_the_listener_to_sessions(): void
    {
        $a = $this->liveSession();
        $this->actingAs($a->user)->post(route('chat.end', $a))->assertRedirect(route('ratings.create', $a));
        $this->assertSame(CounselingSession::ENDED, $a->fresh()->status);
        $this->assertNotNull($a->fresh()->ended_at);
        $this->send($a->user, $a, 'masih bisa?')->assertForbidden();

        $b = $this->liveSession();
        $this->actingAs($b->listener)->post(route('chat.end', $b))->assertRedirect(route('sessions.index'));
    }

    public function test_the_handling_psychologist_can_read_but_not_send(): void
    {
        $session = $this->liveSession();
        $this->send($session->user, $session, 'tolong');
        $psychologist = User::factory()->age(35)->listener(ListenerProfile::PSYCHOLOGIST)->create();
        $session->escalation()->forceCreate([
            'source' => 'listener', 'reason' => 'perlu psikolog',
            'psychologist_id' => $psychologist->id, 'status' => Escalation::ASSIGNED,
        ]);

        $this->actingAs($psychologist)->get(route('chat.show', $session))
            ->assertOk()->assertSee('hanya bisa membaca')->assertDontSee('id="form-kirim"', false);
        $this->actingAs($psychologist)->getJson(route('chat.messages', $session))->assertJsonPath('pesan.0.dari', 'pengguna');
        $this->send($psychologist, $session, 'halo')->assertForbidden();
    }
}
