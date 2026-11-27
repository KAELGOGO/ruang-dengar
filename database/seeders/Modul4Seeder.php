<?php

namespace Database\Seeders;

use App\Models\CounselingSession;
use App\Models\Escalation;
use App\Models\ListenerProfile;
use App\Models\Message;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

/**
 * Module 4 demo data: a second psychologist on duty now (so "Oper ke psikolog" in demo step 5
 * pairs one right away even after the teen in step 3 took the first) and some finished sessions
 * with ratings and an escalation, so the metrics dashboard is not empty.
 * Runs after DatabaseSeeder's demo accounts.
 */
class Modul4Seeder extends Seeder
{
    public function run(): void
    {
        $psikolog2 = User::factory()->age(40)->listener(ListenerProfile::PSYCHOLOGIST)
            ->create(['name' => 'Dr. Bayu (psikolog)', 'email' => 'psikolog2@ruangdengar.test']);
        $psikolog2->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHours(3)]);
        foreach ([1, 2, 3] as $hari) {
            $mulai = now()->addDays($hari)->setTime(19, 0);
            $psikolog2->availabilitySlots()->create(['starts_at' => $mulai, 'ends_at' => $mulai->copy()->addHour()]);
        }

        $akun = fn (string $email) => User::where('email', $email)->firstOrFail();
        [$remaja, $muda, $dewasa] = [$akun('remaja@ruangdengar.test'), $akun('muda@ruangdengar.test'), $akun('dewasa@ruangdengar.test')];
        [$relawan, $mahasiswa, $psikolog] = [$akun('relawan@ruangdengar.test'), $akun('mahasiswa@ruangdengar.test'), $akun('psikolog@ruangdengar.test')];

        // Bima comes back a second time (repeat usage).
        $s = $this->finished($muda, $relawan, 'akademik', now()->subDays(6)->setTime(19, 0));
        $this->chat($s, ['Halo, aku lagi pusing sama tugas akhir.', 'Halo Bima, aku di sini. Cerita aja pelan-pelan.']);
        $this->rate($s, 5, 'Lega banget bisa cerita.');

        $s = $this->finished($muda, $mahasiswa, 'akademik', now()->subDays(3)->setTime(19, 0));
        $this->chat($s, ['Aku mau lanjut cerita soal minggu lalu.', 'Boleh, gimana kabarmu sekarang?']);
        $this->rate($s, 4, null);

        $s = $this->finished($dewasa, $mahasiswa, 'relasi', now()->subDays(5)->setTime(19, 0));
        $this->rate($s, 4, 'Pendengar yang sabar.');

        // An escalated case: flagged message, listener hands over, psychologist takes and resolves it.
        $mulai = now()->subDays(2)->setTime(19, 0);
        $lama = $this->finished($dewasa, $mahasiswa, 'cerita', $mulai);
        $this->chat($lama, ['Akhir-akhir ini berat sekali.', 'Aku dengar kamu. Boleh cerita lebih banyak?', 'Kadang aku pengen mati aja.'], flagIndex: 2);
        $kasus = new Escalation(['source' => 'listener', 'reason' => 'Pengguna menyebut keinginan mengakhiri hidup, perlu ditangani psikolog.']);
        $kasus->forceFill([
            'counseling_session_id' => $lama->id,
            'raised_by' => $mahasiswa->id,
            'psychologist_id' => $psikolog->id,
            'status' => Escalation::RESOLVED,
            'resolved_at' => $mulai->copy()->addHours(2),
            'created_at' => $mulai->copy()->addMinutes(30),
        ])->save();
        $this->rate($lama, 4, null);
        $baru = $this->finished($dewasa, $psikolog, 'cerita', $mulai->copy()->addMinutes(35));
        $this->rate($baru, 5, 'Terima kasih sudah langsung dibantu psikolog.');

        // Teen sessions are always with a psychologist; the last one is left unrated for the demo.
        $this->rate($this->finished($remaja, $psikolog2, 'cerita', now()->subDays(4)->setTime(16, 0)), 5, null);
        $this->finished($remaja, $psikolog, 'akademik', now()->subDay()->setTime(16, 0));
    }

    /** A past session on its own past slot, chatted and ended. */
    private function finished(User $user, User $listener, string $topic, CarbonInterface $start): CounselingSession
    {
        $slot = $listener->availabilitySlots()->create(['starts_at' => $start, 'ends_at' => $start->copy()->addHour()]);

        $session = new CounselingSession(['topic' => $topic, 'scheduled_at' => $start]);
        $session->forceFill([
            'user_id' => $user->id,
            'listener_id' => $listener->id,
            'availability_slot_id' => $slot->id,
            'status' => CounselingSession::ENDED,
            'started_at' => $start->copy()->addMinutes(2),
            'ended_at' => $start->copy()->addMinutes(50),
            'created_at' => $start,
        ])->save();

        return $session;
    }

    /** Alternating user/listener messages; $flagIndex marks the one with a crisis keyword. */
    private function chat(CounselingSession $session, array $bodies, ?int $flagIndex = null): void
    {
        foreach ($bodies as $i => $body) {
            $message = new Message(['body' => $body, 'flagged' => $i === $flagIndex]);
            $message->sender()->associate($i % 2 === 0 ? $session->user_id : $session->listener_id);
            $session->messages()->save($message);
        }
    }

    private function rate(CounselingSession $session, int $score, ?string $comment): void
    {
        $session->rating()->create(['score' => $score, 'comment' => $comment]);
    }
}
