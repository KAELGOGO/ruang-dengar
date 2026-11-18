<?php

namespace Database\Seeders;

use App\Models\ListenerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts for local development and the presentation.
 * Every account uses the factory's default password (see README), local only.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->age(30)->create(['name' => 'Admin Demo', 'email' => 'admin@ruangdengar.test']);

        // Users, one per age tier
        User::factory()->age(15)->create(['name' => 'Rani (15)', 'email' => 'remaja@ruangdengar.test']);
        User::factory()->age(20)->create(['name' => 'Bima (20)', 'email' => 'muda@ruangdengar.test']);
        User::factory()->age(30)->create(['name' => 'Sari (30)', 'email' => 'dewasa@ruangdengar.test']);

        // Listeners
        $relawan = User::factory()->age(20)->listener(ListenerProfile::VOLUNTEER)
            ->create(['name' => 'Dimas (relawan, 20)', 'email' => 'relawan@ruangdengar.test']);
        $mahasiswa = User::factory()->age(22)->listener(ListenerProfile::PSYCH_STUDENT)
            ->create(['name' => 'Nadia (mhs psikologi, 22)', 'email' => 'mahasiswa@ruangdengar.test']);
        $psikolog = User::factory()->age(35)->listener(ListenerProfile::PSYCHOLOGIST)
            ->create(['name' => 'Dr. Laras (psikolog)', 'email' => 'psikolog@ruangdengar.test']);
        User::factory()->age(21)->listener()
            ->create(['name' => 'Tono (menunggu verifikasi)', 'email' => 'pending@ruangdengar.test']);

        // Slots: one running now (for "Bicara sekarang") and a few in the next days.
        foreach ([$relawan, $mahasiswa, $psikolog] as $listener) {
            $listener->availabilitySlots()->create(['starts_at' => now()->subHour(), 'ends_at' => now()->addHours(3)]);
            foreach ([1, 2, 3] as $hari) {
                $mulai = now()->addDays($hari)->setTime(19, 0);
                $listener->availabilitySlots()->create(['starts_at' => $mulai, 'ends_at' => $mulai->copy()->addHour()]);
            }
        }
    }
}
