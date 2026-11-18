<?php

// Crisis contacts shown on /bantuan-darurat and in every chat.
// Checked 19 Nov 2026 (Kemenkes / Healing119). Re-check before the final demo.
return [
    'kontak' => [
        ['nama' => 'Healing119 (Kemenkes), 24 jam', 'nomor' => '119 ext 8', 'link' => 'https://healing119.id'],
        ['nama' => 'Darurat medis', 'nomor' => '119', 'link' => null],
    ],

    // Messages containing any of these are flagged when saved (see Module 4 in README).
    // Plain lowercase substrings; extend with the team.
    'kata_kunci' => [
        'bunuh diri', 'ingin mati', 'pengen mati', 'mau mati', 'akhiri hidup',
        'menyakiti diri', 'melukai diri', 'self harm', 'gak mau hidup', 'tidak mau hidup',
    ],
];
