<?php

namespace App\Support;

/**
 * Moods a user can pick during onboarding. Only shown back to the user themself (never to admins).
 */
class Mood
{
    // key => [emoji, label, what Dengi says back]
    public const OPTIONS = [
        'baik' => ['😊', 'Baik', 'Senang dengarnya! Cerita yang bikin senang juga boleh, lho.'],
        'biasa' => ['😐', 'Biasa saja', 'Hari biasa juga boleh diceritakan kok.'],
        'sedih' => ['😢', 'Sedih', 'Peluk dulu dari aku 🫂 Kamu nggak sendirian.'],
        'cemas' => ['😣', 'Cemas', 'Tarik napas pelan-pelan, ya. Kita hadapi bareng.'],
        'capek' => ['😩', 'Capek', 'Istirahat sebentar di sini, nggak apa-apa.'],
    ];

    public static function reply(?string $mood): ?string
    {
        return self::OPTIONS[$mood][2] ?? null;
    }
}
